import 'package:flutter/material.dart';

/// Design tokens untuk spacing, radius, dan layout yang konsisten.
///
/// Semua widget di AUREX harus menggunakan konstanta dari sini
/// untuk menjaga visual consistency (#4 Theme & UI Polish).
class AppSpacing {
  // ─── Spacing Scale (4px base) ──────────────────────────
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 16;
  static const double lg = 24;
  static const double xl = 32;
  static const double xxl = 48;
  static const double xxxl = 64;

  // ─── Padding ───────────────────────────────────────────
  static const EdgeInsets paddingScreen = EdgeInsets.all(lg);
  static const EdgeInsets paddingCard = EdgeInsets.all(20);
  static const EdgeInsets paddingButton = EdgeInsets.symmetric(vertical: md);
  static const EdgeInsets paddingFormField = EdgeInsets.symmetric(vertical: 14);

  // ─── Margin ────────────────────────────────────────────
  static const EdgeInsets marginCard = EdgeInsets.only(bottom: sm);
  static const EdgeInsets marginSection = EdgeInsets.only(bottom: xxl);

  // ─── Border Radius ─────────────────────────────────────
  static const double radiusXs = 4;
  static const double radiusSm = 8;
  static const double radiusMd = 12;
  static const double radiusLg = 16;
  static const double radiusXl = 20;
  static const double radiusXxl = 24;
  static const double radiusFull = 100;

  // ─── Icon Sizes ────────────────────────────────────────
  static const double iconSm = 18;
  static const double iconMd = 24;
  static const double iconLg = 32;
  static const double iconXl = 64;

  // ─── Avatar ────────────────────────────────────────────
  static const double avatarRadius = 60;
  static const double avatarTextSize = 36;

  // ─── Card Elevation ────────────────────────────────────
  static const double cardElevation = 0;
  static const double cardElevationHover = 2;

  // ─── Button ────────────────────────────────────────────
  static const double buttonHeight = 52;
  static const double buttonMinWidth = 200;

  // ─── Input ─────────────────────────────────────────────
  static const double inputHeight = 56;

  // ─── Score Circle ──────────────────────────────────────
  static const double scoreCircleSize = 180;
  static const double scoreCircleBorderWidth = 4;
  static const double scoreTextSize = 64;
}
