/// Arabic-Indic numeral formatting for display only — storage stays Latin. SPEC §0 / D-09.
extension ArabicNumbers on String {
  static const _map = {
    '0': '٠', '1': '١', '2': '٢', '3': '٣', '4': '٤',
    '5': '٥', '6': '٦', '7': '٧', '8': '٨', '9': '٩',
  };

  /// Convert Latin digits in this string to Arabic-Indic.
  String toArabicDigits() {
    final buffer = StringBuffer();
    for (final ch in split('')) {
      buffer.write(_map[ch] ?? ch);
    }
    return buffer.toString();
  }
}

/// Format a SAR amount for display: thousands separator + Arabic-Indic digits.
String formatSar(num amount) {
  final fixed = amount == amount.roundToDouble()
      ? amount.toStringAsFixed(0)
      : amount.toStringAsFixed(2);
  final parts = fixed.split('.');
  final intPart = parts[0].replaceAllMapped(
    RegExp(r'\B(?=(\d{3})+(?!\d))'),
    (m) => '٬',
  );
  final joined = parts.length > 1 ? '$intPart٫${parts[1]}' : intPart;
  return '${joined.toArabicDigits()} ر.س';
}
