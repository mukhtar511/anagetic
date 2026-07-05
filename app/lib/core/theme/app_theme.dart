import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import 'app_colors.dart';

/// The Anaqatuk theme — El Messiri for headings, IBM Plex Sans Arabic for body,
/// RTL, arch-like rounded corners. SPEC §0.
abstract final class AppTheme {
  static ThemeData light() {
    final base = ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: AppColors.roseBg,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.maroon,
        primary: AppColors.maroon,
        secondary: AppColors.gold,
        surface: AppColors.white,
        brightness: Brightness.light,
      ),
    );

    final bodyFont = GoogleFonts.ibmPlexSansArabicTextTheme(base.textTheme);
    final headingFont = GoogleFonts.elMessiri();

    return base.copyWith(
      textTheme: bodyFont.copyWith(
        displayLarge: headingFont.copyWith(
            color: AppColors.maroon, fontWeight: FontWeight.w700),
        displayMedium: headingFont.copyWith(
            color: AppColors.maroon, fontWeight: FontWeight.w700),
        headlineSmall: headingFont.copyWith(
            color: AppColors.maroon, fontWeight: FontWeight.w700),
        titleLarge: headingFont.copyWith(
            color: AppColors.maroon, fontWeight: FontWeight.w600),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.white,
        foregroundColor: AppColors.maroon,
        elevation: 0,
        centerTitle: false,
      ),
      cardTheme: CardThemeData(
        color: AppColors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(18),
          side: const BorderSide(color: AppColors.roseSoft),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.roseBg,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.roseSoft),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.roseSoft),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.gold),
        ),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.maroon,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(99),
          ),
        ),
      ),
    );
  }
}
