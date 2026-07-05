import 'package:flutter/material.dart';

/// Brand palette — taken verbatim from the prototype's CSS `:root`. SPEC §0.
abstract final class AppColors {
  static const maroon = Color(0xFF6D28A9); // بنفسجي ملكي — الأساسي
  static const maroonDeep = Color(0xFF4A1878); // بنفسجي عميق
  static const roseBg = Color(0xFFF6EEFC); // لافندر — خلفية
  static const roseSoft = Color(0xFFE3CDF5); // لافندر — حدود
  static const gold = Color(0xFFB98A4E);
  static const goldLight = Color(0xFFD9B98A);
  static const ink = Color(0xFF2E2438);
  static const white = Color(0xFFFFFEFF);
  static const green = Color(0xFF4A6B4F);

  // Semantic helpers used across cards/badges.
  static const danger = Color(0xFFA33333);
  static const warnBg = Color(0xFFF4E9D6);
  static const warnInk = Color(0xFF8A6420);
  static const okBg = Color(0xFFEAF3EB);
  static const mutedInk = Color(0xFF8A707A);
}
