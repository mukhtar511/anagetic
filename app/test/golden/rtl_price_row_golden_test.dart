import 'package:anaqatuk/core/theme/app_colors.dart';
import 'package:anaqatuk/core/utils/arabic_numbers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

/// Golden test locking the RTL layout + Arabic-Indic price rendering. SPEC §8.
/// Baseline generated with: flutter test --update-goldens
void main() {
  testWidgets('price row renders right-to-left with Arabic-Indic digits',
      (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        debugShowCheckedModeBanner: false,
        home: Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(
            backgroundColor: AppColors.roseBg,
            body: Center(
              child: RepaintBoundary(
                child: Container(
                  width: 300,
                  padding: const EdgeInsets.all(16),
                  color: AppColors.white,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('الإجمالي',
                          style: TextStyle(
                              color: AppColors.ink,
                              fontWeight: FontWeight.w700)),
                      Text(formatSar(1250),
                          style: const TextStyle(
                              color: AppColors.maroon,
                              fontWeight: FontWeight.w700)),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );

    await expectLater(
      find.byType(RepaintBoundary).first,
      matchesGoldenFile('goldens/price_row_rtl.png'),
    );
  });
}
