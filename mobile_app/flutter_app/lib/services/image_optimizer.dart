import 'dart:io';
import 'package:flutter_image_compress/flutter_image_compress.dart';

/// Service untuk optimasi gambar sebelum upload.
///
/// Menggunakan flutter_image_compress untuk:
/// - Resize gambar ke resolusi maksimal tertentu
/// - Kompresi JPEG dengan quality tertentu
/// - Menyimpan hasil kompresi ke temporary directory
///
/// Target: ukuran file < 1MB untuk mempercepat upload di jaringan lambat.
class ImageOptimizer {
  static const int defaultQuality = 70;
  static const int defaultMaxWidth = 1024;
  static const int defaultMaxHeight = 1024;
  static const int maxFileSizeBytes = 1 * 1024 * 1024; // 1 MB

  /// Optimasi gambar: resize & kompresi.
  ///
  /// [sourcePath] — path file asli
  /// [quality] — kualitas JPEG (1–100), makin rendah = makin kecil
  /// [maxWidth] / [maxHeight] — batas resolusi maksimal
  ///
  /// Returns path ke file hasil kompresi, atau [sourcePath] jika gagal.
  static Future<String> optimize({
    required String sourcePath,
    int quality = defaultQuality,
    int maxWidth = defaultMaxWidth,
    int maxHeight = defaultMaxHeight,
  }) async {
    try {
      // Cek ukuran file asli — jika sudah kecil, skip kompresi
      final sourceFile = File(sourcePath);
      if (await sourceFile.exists()) {
        final size = await sourceFile.length();
        if (size < maxFileSizeBytes && size > 0) {
          return sourcePath;
        }
      }

      // Simpan hasil kompresi ke system temp directory
      final dir = Directory.systemTemp;
      final filename = 'optimized_${DateTime.now().millisecondsSinceEpoch}.jpg';
      final targetPath = '${dir.path}${Platform.pathSeparator}$filename';

      final result = await FlutterImageCompress.compressAndGetFile(
        sourcePath,
        targetPath,
        quality: quality,
        minWidth: maxWidth,
        minHeight: maxHeight,
        format: CompressFormat.jpeg,
      );

      if (result != null) {
        final resultFile = File(result.path);
        if (await resultFile.exists()) {
          return result.path;
        }
      }

      return sourcePath;
    } catch (e) {
      return sourcePath;
    }
  }

  /// Optimasi dengan estimasi ukuran — coba turunkan quality
  /// jika hasil kompresi masih terlalu besar (> 1 MB).
  ///
  /// Selalu mengompres dari [sourcePath] asli (bukan hasil kompresi
  /// sebelumnya) untuk menghindari degradasi kualitas akibat
  /// re-kompresi JPEG yang sudah terkompresi.
  static Future<String> optimizeWithSizeTarget({
    required String sourcePath,
    int targetBytes = maxFileSizeBytes,
    int maxWidth = defaultMaxWidth,
    int maxHeight = defaultMaxHeight,
  }) async {
    String lastResult = sourcePath;
    int currentQuality = defaultQuality;

    for (int attempt = 0; attempt < 3; attempt++) {
      // Selalu kompres dari file asli, hanya turunkan quality
      final result = await optimize(
        sourcePath: sourcePath,
        quality: currentQuality,
        maxWidth: maxWidth,
        maxHeight: maxHeight,
      );

      // Jika hasil optimize sama dengan sourcePath, artinya file sudah kecil
      if (result == sourcePath) return sourcePath;

      lastResult = result;

      final file = File(result);
      if (await file.exists()) {
        final size = await file.length();
        if (size <= targetBytes) return result;
      }

      // Turunkan quality untuk percobaan berikutnya
      currentQuality = (currentQuality * 0.7).round();
      if (currentQuality < 20) currentQuality = 20;
    }

    return lastResult;
  }
}
