// Spoken names for the profile emoji choices (config/study-room.php `emojis`).
// Screen readers announce emoji in the reader's own language, which is often
// not Chinese, so each radio gets an explicit zh-TW label.
const EMOJI_NAMES = {
  '📚': '書本',
  '✏️': '鉛筆',
  '🖊️': '原子筆',
  '📖': '打開的書',
  '🧠': '大腦',
  '💡': '燈泡',
  '☕': '咖啡',
  '🍵': '茶',
  '🌱': '幼苗',
  '🌸': '櫻花',
  '🍀': '四葉草',
  '⭐': '星星',
  '🌙': '月亮',
  '☀️': '太陽',
  '🐱': '貓',
  '🐶': '狗',
  '🐰': '兔子',
  '🐼': '熊貓',
  '🦉': '貓頭鷹',
  '🐧': '企鵝',
  '🍎': '蘋果',
  '🍞': '麵包',
  '🧁': '杯子蛋糕',
  '🎧': '耳機',
  '🎯': '靶心',
  '🔥': '火焰',
  '🏃': '跑步的人',
  '🧩': '拼圖',
}

export function emojiName(emoji) {
  return EMOJI_NAMES[emoji] ?? emoji
}
