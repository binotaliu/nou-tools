import { nextTick, reactive } from 'vue'

// One Tab stop per floor instead of one per seat (up to 120), using a roving
// tabindex: the floor's seat area is a group, exactly one seat in it has
// tabindex 0, and the arrow keys move focus between seats.
//
// Left/Right follow reading (DOM) order, so they walk a row and wrap into the
// next one, table by table. Up/Down pick the nearest seat in that direction
// by on-screen position, which suits the 3/4/6-column solo grid and the
// tables around it without knowing the breakpoint. Home/End go to the
// floor's first and last seat. Every seat button carries `data-seat-code`.
export default function useSeatRovingFocus(socket) {
  // floor number → code of the seat that holds the floor's Tab stop
  const activeCodes = reactive({})

  function floorSeats(floor) {
    return [...floor.soloSeats, ...floor.tables.flatMap(table => table.seats)]
  }

  // A remembered seat that still exists wins; otherwise your own seat, then
  // the first free one, then the first seat of all.
  function activeCodeFor(floor) {
    const seats = floorSeats(floor)
    const remembered = activeCodes[floor.floor]

    if (remembered && seats.some(seat => seat.code === remembered)) {
      return remembered
    }

    const fallback =
      seats.find(seat => seat.code === socket.heldSeatCode) ||
      seats.find(seat => !seat.isOccupied) ||
      seats[0]

    return fallback ? fallback.code : null
  }

  function tabIndexFor(floor, seat) {
    return activeCodeFor(floor) === seat.code ? 0 : -1
  }

  function seatElement(code) {
    return document.querySelector('[data-seat-code="' + code + '"]')
  }

  // The seats currently on screen in one view: map buttons and list-view
  // buttons both carry data-seat-code, so scope to the group.
  function groupSeatElements(group) {
    return [...group.querySelectorAll('[data-seat-code]')]
  }

  function center(element) {
    const rect = element.getBoundingClientRect()

    return { x: rect.left + rect.width / 2, y: rect.top + rect.height / 2 }
  }

  function nearestVertically(elements, from, direction) {
    const origin = center(from)
    let best = null
    let bestScore = Infinity

    for (const element of elements) {
      if (element === from) {
        continue
      }

      const point = center(element)
      const along = (point.y - origin.y) * direction

      // Needs to be clearly above/below, not just a pixel off the same row.
      if (along < 8) {
        continue
      }

      const score = along + Math.abs(point.x - origin.x) * 2

      if (score < bestScore) {
        bestScore = score
        best = element
      }
    }

    return best
  }

  function onKeydown(event, floor) {
    const current = event.target.closest('[data-seat-code]')

    if (!current) {
      return
    }

    const elements = groupSeatElements(event.currentTarget)
    const index = elements.indexOf(current)
    let target = null

    switch (event.key) {
      case 'ArrowRight':
        target = elements[index + 1]
        break
      case 'ArrowLeft':
        target = elements[index - 1]
        break
      case 'ArrowDown':
        target = nearestVertically(elements, current, 1)
        break
      case 'ArrowUp':
        target = nearestVertically(elements, current, -1)
        break
      case 'Home':
        target = elements[0]
        break
      case 'End':
        target = elements[elements.length - 1]
        break
      default:
        return
    }

    // Arrow keys at an edge still must not scroll the page.
    event.preventDefault()

    if (target) {
      activeCodes[floor.floor] = target.dataset.seatCode
      target.focus()
    }
  }

  // Clicking or tabbing onto a seat moves the floor's Tab stop there too.
  function onFocusIn(event, floor) {
    const seat = event.target.closest('[data-seat-code]')

    if (seat) {
      activeCodes[floor.floor] = seat.dataset.seatCode
    }
  }

  async function focusSeat(code) {
    const floor = socket.floorForSeat ? socket.floorForSeat(code) : null

    if (floor) {
      activeCodes[floor.floor] = code
    }

    await nextTick()
    seatElement(code)?.focus()
  }

  return reactive({
    activeCodeFor,
    tabIndexFor,
    onKeydown,
    onFocusIn,
    focusSeat,
  })
}
