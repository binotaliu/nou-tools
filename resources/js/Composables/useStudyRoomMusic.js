import { computed, reactive, ref } from 'vue'

// Both keys are versioned like the checklist's, since readers keep them.
const VOLUME_KEY = 'nou:study-room:music-volume:v1'
const PLAYLIST_KEY = 'nou:study-room:music-playlist:v1'
const DEFAULT_VOLUME = 0.6
// Past this many seconds "previous" restarts the track instead of skipping.
const RESTART_THRESHOLD_SECONDS = 3

function readStorage(key) {
  try {
    return window.localStorage.getItem(key)
  } catch {
    return null
  }
}

function writeStorage(key, value) {
  try {
    window.localStorage.setItem(key, String(value))
  } catch {
    // Private windows and blocked storage just forget the preference.
  }
}

function clamp01(value) {
  return Math.min(1, Math.max(0, Number(value) || 0))
}

// The cassette player on the Wall. Playlists come from
// GET /study-room/music/playlists (src/Domains/Music). Playback is local to
// this listener: nothing is synced through the socket. A "tape" is loaded
// (`loaded`) once the listener presses play or picks a playlist, and ejecting
// unloads it — that is what the deck's window shows.
export default function useStudyRoomMusic() {
  const playlists = ref([])
  const playlistIndex = ref(0)
  const trackIndex = ref(0)
  const loaded = ref(false)
  const playing = ref(false)
  const currentTime = ref(0)
  const duration = ref(0)
  const volume = ref(clamp01(readStorage(VOLUME_KEY) ?? DEFAULT_VOLUME))
  const errored = ref(false)

  const available = computed(() => playlists.value.length > 0)
  const playlist = computed(() => playlists.value[playlistIndex.value] ?? null)
  const track = computed(() =>
    playlist.value ? (playlist.value.tracks[trackIndex.value] ?? null) : null
  )
  // 0..1 through the current track, drives how much tape is on each reel.
  const progress = computed(() =>
    duration.value > 0 ? Math.min(1, currentTime.value / duration.value) : 0
  )

  let audio = null
  // Tracks that failed in a row, so an unplayable playlist stops instead of
  // skipping forever.
  let consecutiveFailures = 0

  async function load() {
    try {
      const response = await window.axios.get('/study-room/music/playlists')

      playlists.value = response.data.playlists ?? []

      const savedId = Number(readStorage(PLAYLIST_KEY))
      const savedIndex = playlists.value.findIndex(item => item.id === savedId)

      playlistIndex.value = savedIndex >= 0 ? savedIndex : 0
    } catch {
      // Music is a nicety: without playlists the deck simply isn't shown.
    }
  }

  function sourceFor(item) {
    return audio.canPlayType('audio/mpeg') ? item.audioMp3Url : item.audioOggUrl
  }

  // Created on the first play, from a user gesture, so autoplay policies
  // never get in the way and no audio loads before the listener asks.
  function ensureAudio() {
    if (audio) {
      return audio
    }

    audio = new Audio()
    audio.preload = 'auto'
    audio.volume = volume.value

    audio.addEventListener('timeupdate', () => {
      currentTime.value = audio.currentTime
    })
    audio.addEventListener('durationchange', () => {
      if (Number.isFinite(audio.duration)) {
        duration.value = audio.duration
      }
    })
    audio.addEventListener('playing', () => {
      consecutiveFailures = 0
      errored.value = false
      playing.value = true
    })
    // Also fires for the OS's own controls (media keys, lock screen).
    audio.addEventListener('pause', () => {
      playing.value = false
    })
    audio.addEventListener('ended', next)
    audio.addEventListener('error', handleError)

    return audio
  }

  async function play() {
    ensureAudio()

    try {
      await audio.play()
      playing.value = true
    } catch {
      // Interrupted by a newer load(), or blocked: stay paused.
      playing.value = false
    }
  }

  function pause() {
    if (audio) {
      audio.pause()
    }

    playing.value = false
  }

  function insertTrack(index) {
    const item = playlist.value.tracks[index]

    ensureAudio()
    trackIndex.value = index
    loaded.value = true
    currentTime.value = 0
    duration.value = item.durationSeconds
    audio.src = sourceFor(item)
    updateMediaSession()

    return play()
  }

  function handleError() {
    if (!loaded.value || !playlist.value) {
      return
    }

    consecutiveFailures += 1

    if (consecutiveFailures >= playlist.value.tracks.length) {
      consecutiveFailures = 0
      errored.value = true
      playing.value = false

      return
    }

    next()
  }

  function toggle() {
    if (!available.value) {
      return Promise.resolve()
    }

    if (!loaded.value) {
      return insertTrack(trackIndex.value)
    }

    if (playing.value) {
      pause()

      return Promise.resolve()
    }

    return play()
  }

  function next() {
    if (!playlist.value) {
      return Promise.resolve()
    }

    return insertTrack((trackIndex.value + 1) % playlist.value.tracks.length)
  }

  function previous() {
    if (!playlist.value) {
      return Promise.resolve()
    }

    if (loaded.value && currentTime.value > RESTART_THRESHOLD_SECONDS) {
      return seek(0)
    }

    const count = playlist.value.tracks.length

    return insertTrack((trackIndex.value - 1 + count) % count)
  }

  function selectPlaylist(index) {
    if (!playlists.value[index]) {
      return Promise.resolve()
    }

    playlistIndex.value = index
    writeStorage(PLAYLIST_KEY, playlists.value[index].id)

    return insertTrack(0)
  }

  // Takes the tape out, leaving the deck empty until a playlist is chosen.
  function eject() {
    if (!loaded.value) {
      return
    }

    pause()
    audio.removeAttribute('src')
    audio.load()
    loaded.value = false
    currentTime.value = 0
    duration.value = 0
    errored.value = false
  }

  function seek(seconds) {
    if (audio && loaded.value) {
      audio.currentTime = seconds
    }

    currentTime.value = seconds
  }

  function setVolume(value) {
    volume.value = clamp01(value)
    writeStorage(VOLUME_KEY, volume.value)

    if (audio) {
      audio.volume = volume.value
    }
  }

  function updateMediaSession() {
    if (!('mediaSession' in navigator) || !track.value) {
      return
    }

    const artwork = playlist.value.coverImageUrl
      ? [{ src: playlist.value.coverImageUrl }]
      : []

    navigator.mediaSession.metadata = new window.MediaMetadata({
      title: track.value.title,
      artist: track.value.author,
      album: playlist.value.title,
      artwork,
    })

    navigator.mediaSession.setActionHandler('play', toggle)
    navigator.mediaSession.setActionHandler('pause', pause)
    navigator.mediaSession.setActionHandler('nexttrack', next)
    navigator.mediaSession.setActionHandler('previoustrack', previous)
  }

  // Called when the page unmounts so the music doesn't outlive the room.
  function dispose() {
    if (audio) {
      audio.pause()
      audio.removeAttribute('src')
      audio.load()
      audio = null
    }

    if ('mediaSession' in navigator) {
      navigator.mediaSession.metadata = null
    }

    playing.value = false
    loaded.value = false
  }

  return reactive({
    playlists,
    playlistIndex,
    trackIndex,
    loaded,
    playing,
    currentTime,
    duration,
    volume,
    errored,
    available,
    playlist,
    track,
    progress,
    load,
    toggle,
    next,
    previous,
    selectPlaylist,
    eject,
    seek,
    setVolume,
    dispose,
  })
}
