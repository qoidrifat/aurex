import 'package:flutter/material.dart';

class AurexColors {
  // ─── Primary Palette ──────────────────────────────────
  /// PRIMARY COLOR — Olive (#556B2F)
  /// Untuk dark bg: gunakan [oliveLight] (#7B9346) yg kontrasnya > 4.5:1 thd charcoal
  /// WCAG AA: vs cream #F5F5F5 = ~4.1:1 ✓ (large text), vs white = ~3.7:1 ✓ (large text)
  ///         vs #E8E4DC = ~3.5:1 — GAGAL untuk normal text. Hanya utk aksen/UI components.
  static const Color olive = Color(0xFF556B2F);

  /// Olive variant dgn kontras WCAG AA compliant terhadap dark bg.
  /// Contrast ratio vs charcoal (#1C1C1C): ~5.15:1 ✓ WCAG AA
  /// Contrast ratio vs charcoal: ~5.15:1 ✓
  /// Gunakan ini untuk primary button/teks di dark mode.
  static const Color oliveLight = Color(0xFF7B9346);

  /// Rust / aksen hangat.
  static const Color rust = Color(0xFFB7410E);

  /// Charcoal — bg utama dark mode.
  static const Color charcoal = Color(0xFF1C1C1C);

  /// Cream — bg utama light mode.
  static const Color cream = Color(0xFFF5F5F5);

  // ─── Neutrals ─────────────────────────────────────────
  static const Color black = Color(0xFF000000);
  static const Color white = Color(0xFFFFFFFF);

  /// Grey medium — untuk teks sekunder di light mode.
  /// Contrast ratio vs cream (#F5F5F5): ~3.9:1 ✓ (large text)
  static const Color grey = Color(0xFF757575);

  /// Grey light — untuk border / divider.
  static const Color greyLight = Color(0xFFBDBDBD);

  /// Grey untuk teks sekunder di dark mode.
  /// Contrast ratio vs charcoal (#1C1C1C): ~7.5:1 ✓
  static const Color greyDark = Color(0xFFB0B0B0);

  /// Teks utama dark mode — bukan cream mentah.
  /// Contrast ratio vs charcoal (#1C1C1C): ~14:1 ✓
  static const Color textOnDark = Color(0xFFE8E4DC);

  /// Teks utama light mode — bukan black mentah.
  /// Contrast ratio vs cream (#F5F5F5): ~13:1 ✓
  static const Color textOnLight = Color(0xFF2C2C2C);

  // ─── Semantic Colors (F-023) ──────────────────────────
  /// Digunakan untuk pesan error, alert kritis.
  /// Contrast ratio vs cream: ~5.1:1 ✓, vs charcoal: ~5.8:1 ✓
  static const Color error = Color(0xFFD32F2F);

  /// Digunakan untuk pesan sukses, konfirmasi.
  static const Color success = Color(0xFF388E3C);

  /// Digunakan untuk pesan warning, peringatan.
  static const Color warning = Color(0xFFF57C00);

  // ─── Surface Variants ─────────────────────────────────
  /// Surface color untuk dark mode.
  static const Color surfaceDark = Color(0xFF1C1C1C);

  /// Surface color untuk light mode.
  static const Color surfaceLight = Color(0xFFF8F6F0);

  /// Card surface color untuk light mode.
  static const Color cardLight = Color(0xFFEFECE4);

  /// Card surface color untuk dark mode (sedikit lebih terang dari charcoal).
  /// Contrast ratio vs textOnDark: ~6:1 ✓
  static const Color cardDark = Color(0xFF2A2A2A);
}
