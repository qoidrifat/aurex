import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';
import 'dio_provider.dart';

/// Model untuk offline queue item.
class OfflineQueueItem {
  final String id;
  final String type; // 'upload_selfie'
  final Map<String, dynamic> data;
  final DateTime createdAt;
  int retryCount;
  String? lastError;

  OfflineQueueItem({
    required this.id,
    required this.type,
    required this.data,
    required this.createdAt,
    this.retryCount = 0,
    this.lastError,
  });

  Map<String, dynamic> toJson() => {
        'id': id,
        'type': type,
        'data': data,
        'createdAt': createdAt.toIso8601String(),
        'retryCount': retryCount,
        'lastError': lastError,
      };

  factory OfflineQueueItem.fromJson(Map<String, dynamic> json) =>
      OfflineQueueItem(
        id: json['id'] as String,
        type: json['type'] as String,
        data: json['data'] as Map<String, dynamic>,
        createdAt: DateTime.parse(json['createdAt'] as String),
        retryCount: json['retryCount'] as int? ?? 0,
        lastError: json['lastError'] as String?,
      );
}

/// Service untuk offline queue.
///
/// Item #5 Bulan 3 — Flutter Offline Support:
/// Menyimpan request yang gagal (misalnya upload selfie saat offline)
/// ke SharedPreferences, lalu mengirimkannya kembali saat koneksi pulih.
///
/// Fitur:
/// - Queue request offline
/// - Auto-retry saat app dibuka kembali
/// - Maksimal 3 retry per item
/// - Hapus item yang sudah > 24 jam
/// - Persist queue ke SharedPreferences (survive app restart)
class OfflineQueueService {
  static const _queueKey = 'offline_queue';
  static const _maxRetries = 3;
  static const _maxAge = Duration(hours: 24);

  final ApiService _apiService;
  List<OfflineQueueItem> _queue = [];

  OfflineQueueService(this._apiService);

  /// Inisialisasi: load queue dari SharedPreferences
  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    final stored = prefs.getString(_queueKey);
    if (stored != null) {
      final list = jsonDecode(stored) as List<dynamic>;
      _queue = list
          .map((e) => OfflineQueueItem.fromJson(e as Map<String, dynamic>))
          .where((item) => DateTime.now().difference(item.createdAt) < _maxAge)
          .toList();
      await _persist();
    }
  }

  /// Tambah item ke queue (simpan untuk retry nanti)
  Future<void> enqueue(OfflineQueueItem item) async {
    _queue.add(item);
    await _persist();
  }

  /// Proses semua item yang antri (panggil saat koneksi pulih)
  Future<QueueResult> processQueue() async {
    if (_queue.isEmpty) {
      return QueueResult(processed: 0, failed: 0, errors: []);
    }

    int processed = 0;
    int failed = 0;
    final List<String> errors = [];

    final snapshot = List<OfflineQueueItem>.from(_queue);
    for (final item in snapshot) {
      if (item.retryCount >= _maxRetries) {
        _queue.remove(item);
        errors.add('${item.type}:${item.id} — Max retries exceeded');
        failed++;
        continue;
      }

      if (DateTime.now().difference(item.createdAt) > _maxAge) {
        _queue.remove(item);
        errors.add('${item.type}:${item.id} — Expired');
        failed++;
        continue;
      }

      try {
        await _processItem(item);
        _queue.remove(item);
        processed++;
      } catch (e) {
        item.retryCount++;
        item.lastError = e.toString();
        errors.add('${item.type}:${item.id} — ${e.toString()}');
        failed++;
      }
    }

    await _persist();
    return QueueResult(processed: processed, failed: failed, errors: errors);
  }

  /// Proses satu item queue
  Future<void> _processItem(OfflineQueueItem item) async {
    switch (item.type) {
      case 'upload_selfie':
        final imagePath = item.data['image_path'] as String;
        final file = File(imagePath);
        if (!await file.exists()) {
          throw Exception('File tidak ditemukan: $imagePath');
        }
        await _apiService.uploadSelfie(imagePath);
        break;
      default:
        throw Exception('Unknown queue type: ${item.type}');
    }
  }

  /// Dapatkan jumlah item yang antri
  int get pendingCount => _queue.length;

  /// Dapatkan daftar item yang antri
  List<OfflineQueueItem> get pendingItems => List.unmodifiable(_queue);

  /// Hapus semua item queue
  Future<void> clear() async {
    _queue.clear();
    await _persist();
  }

  /// Persist queue ke SharedPreferences
  Future<void> _persist() async {
    final prefs = await SharedPreferences.getInstance();
    final encoded = jsonEncode(_queue.map((e) => e.toJson()).toList());
    await prefs.setString(_queueKey, encoded);
  }
}

/// Result dari proses queue
class QueueResult {
  final int processed;
  final int failed;
  final List<String> errors;

  QueueResult({
    required this.processed,
    required this.failed,
    required this.errors,
  });

  bool get hasErrors => errors.isNotEmpty;
}

/// Riverpod provider untuk OfflineQueueService
final offlineQueueProvider = Provider<OfflineQueueService>((ref) {
  return OfflineQueueService(ref.read(apiServiceProvider));
});

/// Provider untuk status pending queue count
final pendingQueueCountProvider = FutureProvider<int>((ref) async {
  final service = ref.read(offlineQueueProvider);
  return service.pendingCount;
});
