import 'package:flutter/foundation.dart';

/// Global tracker untuk request_id end-to-end tracing.
///
/// Menyimpan request_id terakhir dari response API dan mengeksposnya
/// sebagai ValueNotifier agar widget/provider bisa mendengarkan perubahan.
///
/// Flow:
///   Flutter → [X-Request-Id] → Laravel → AI Service
///   Flutter ← [X-Request-Id] ← Laravel ← AI Service
class RequestTracker extends ValueNotifier<String?> {
  RequestTracker() : super(null);

  /// Set request_id dari response header X-Request-Id
  void setRequestId(String? id) {
    if (id != null && id != value) {
      value = id;
    }
  }

  /// Clear request_id (misalnya setelah logout)
  void clear() {
    value = null;
  }

  /// Dapatkan request_id saat ini
  String? get requestId => value;

  /// Format untuk ditampilkan di log/debug
  String get displayId {
    if (value == null) return 'N/A';
    return value!.length > 8
        ? value!.substring(0, 8)
        : value!;
  }
}

/// Instance global untuk RequestTracker
final globalRequestTracker = RequestTracker();
