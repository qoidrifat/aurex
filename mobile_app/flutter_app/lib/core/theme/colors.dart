import 'package:flutter/material.dart';

class AurexColors {
  // ─── Primary Palette ──────────────────────────────────
  static const Color olive = Color(0xFF556B2F);
  static const Color rust = Color(0xFFB7410E);
  static const Color charcoal = Color(0xFF1C1C1C);
  static const Color cream = Color(0xFFF5F5F5);

  // ─── Neutrals ─────────────────────────────────────────
  static const Color black = Color(0xFF000000);
  static const Color white = Color(0xFFFFFFFF);
  static const Color grey = Color(0xFF9E9E9E);
  static const Color greyLight = Color(0xFFBDBDBD);

  // ─── Semantic Colors (F-023) ──────────────────────────
  /// Digunakan untuk pesan error, alert kritis
  static const Color error = Color(0xFFD32F2F);

  /// Digunakan untuk pesan sukses, konfirmasi
  static const Color success = Color(0xFF388E3C);

  /// Digunakan untuk pesan warning, peringatan
  static const Color warning = Color(0xFFF57C00);

  // ─── Surface Variants ─────────────────────────────────
  /// Surface color untuk dark mode
  static const Color surfaceDark = Color(0xFF1C1C1C);

  /// Surface color untuk light mode
  static const Color surfaceLight = Color(0xFFF8F6F0);

  /// Card surface color untuk light mode
  static const Color cardLight = Color(0xFFEFECE4);
}
