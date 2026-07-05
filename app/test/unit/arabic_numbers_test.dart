import 'package:anaqatuk/core/utils/arabic_numbers.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('Arabic-Indic formatting (SPEC §0)', () {
    test('converts Latin digits to Arabic-Indic', () {
      expect('1234567890'.toArabicDigits(), '١٢٣٤٥٦٧٨٩٠');
    });

    test('formatSar renders whole amounts with separators + suffix', () {
      expect(formatSar(1250), '١٬٢٥٠ ر.س');
      expect(formatSar(85), '٨٥ ر.س');
    });

    test('formatSar keeps two decimals when fractional', () {
      expect(formatSar(434.4), '٤٣٤٫٤٠ ر.س');
    });
  });
}
