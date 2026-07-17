import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Typography system untuk AUREX.
///
/// Semua style menggunakan [GoogleFonts.poppins] dengan ukuran dasar tetap,
/// namun Flutter secara otomatis menerapkan [MediaQuery.textScaleFactor]
/// pada widget [Text] yang menggunakan style ini.
///
/// Untuk kontrol lebih lanjut (misalnya capping scale factor), gunakan
/// metode [scale] yang menerima [BuildContext] dan mengembalikan [TextStyle]
/// dengan fontSize yang sudah diskalakan terbatas (clamped 0.8–1.3).
class AurexTypography {
  // ─── Base Styles ──────────────────────────────────

  /// Heading 1 — paling besar, untuk judul utama.
  /// Warna teks diwarisi dari Theme / DefaultTextStyle agar otomatis
  /// menyesuaikan saat dark/light mode berubah.
  static TextStyle get heading1 => GoogleFonts.poppins(
        fontSize: 32,
        fontWeight: FontWeight.bold,
      );

  /// Heading 2 — untuk sub-judul.
  static TextStyle get heading2 => GoogleFonts.poppins(
        fontSize: 24,
        fontWeight: FontWeight.bold,
      );

  /// Body Large — untuk teks utama.
  static TextStyle get bodyLarge => GoogleFonts.poppins(
        fontSize: 18,
        fontWeight: FontWeight.normal,
      );

  /// Body Medium — untuk teks sekunder / deskripsi.
  static TextStyle get bodyMedium => GoogleFonts.poppins(
        fontSize: 16,
        fontWeight: FontWeight.normal,
      );

  /// Button Text — untuk tombol.
  static TextStyle get buttonText => GoogleFonts.poppins(
        fontSize: 16,
        fontWeight: FontWeight.w600,
      );

  // ─── Scaled (Accessibility) Helpers ────────────────

  /// Mengembalikan [heading1] yang diskalakan dengan [textScaleFactor]
  /// dari [context], dibatasi antara 0.8× hingga 1.3× ukuran dasar.
  static TextStyle heading1Scaled(BuildContext context) =>
      _scale(context, heading1, 32);

  /// Mengembalikan [bodyMedium] yang diskalakan dengan [textScaleFactor].
  static TextStyle bodyMediumScaled(BuildContext context) =>
      _scale(context, bodyMedium, 16);

  /// Terapkan [textScaleFactor] ke [style] dengan clamping.
  static TextStyle _scale(BuildContext context, TextStyle style, double baseSize) {
    final scale = MediaQuery.textScaleFactorOf(context).clamp(0.8, 1.3);
    return style.copyWith(fontSize: baseSize * scale);
  }

  // ─── Color Helpers ────────────────────────────────

  /// Helper: heading1 dengan warna khusus (jika perlu override)
  static TextStyle heading1WithColor(Color color) =>
      heading1.copyWith(color: color);

  /// Helper: heading2 dengan warna khusus
  static TextStyle heading2WithColor(Color color) =>
      heading2.copyWith(color: color);

  /// Helper: bodyMedium dengan warna khusus (untuk teks sekunder)
  static TextStyle bodyMediumWithColor(Color color) =>
      bodyMedium.copyWith(color: color);
}
