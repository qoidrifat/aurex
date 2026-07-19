import 'package:flutter/material.dart';
import '../core/theme/colors.dart';
import '../core/theme/typography.dart';

/// Error Boundary Widget untuk Flutter.
///
/// Item #6 Bulan 3 — Error Boundary:
/// Menangkap error/widget build exception di widget tree dan
/// menampilkan fallback UI yang user-friendly.
///
/// Mirip konsep Error Boundary di React:
/// - Menangkap error di build method child widgets
/// - Mencegah app crash total
/// - Menampilkan fallback UI
/// - Opsi retry (rebuild child)
///
/// Usage:
/// ```dart
/// ErrorBoundary(
///   onError: (error, stack) => Sentry.captureException(error, stackTrace: stack),
///   child: MyWidget(),
/// )
/// ```
class ErrorBoundary extends StatefulWidget {
  /// Widget yang ingin dilindungi
  final Widget child;

  /// Callback saat error terjadi (untuk logging/Sentry)
  final void Function(Object error, StackTrace stack)? onError;

  /// Custom fallback builder (opsional)
  final Widget Function(BuildContext context, Object error)? fallbackBuilder;

  const ErrorBoundary({
    super.key,
    required this.child,
    this.onError,
    this.fallbackBuilder,
  });

  @override
  State<ErrorBoundary> createState() => _ErrorBoundaryState();
}

class _ErrorBoundaryState extends State<ErrorBoundary> {
  Object? _error;
  StackTrace? _stackTrace;

  @override
  void initState() {
    super.initState();
    // FlutterError handler untuk menangkap error dari framework
    FlutterError.onError = (FlutterErrorDetails details) {
      FlutterError.presentError(details);
      widget.onError?.call(details.exception, details.stack ?? StackTrace.empty);
    };
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null) {
      return widget.fallbackBuilder != null
          ? widget.fallbackBuilder!(context, _error!)
          : _DefaultErrorFallback(
              error: _error!,
              onRetry: _retry,
            );
    }
    return GestureDetector(
      onPanUpdate: (_) {},
      child: Builder(
        builder: (context) => widget.child,
      ),
    );
  }

  void _retry() {
    setState(() {
      _error = null;
      _stackTrace = null;
    });
  }

  @override
  void didCatchError(Object error, StackTrace stack) {
    super.didCatchError(error, stack);
    setState(() {
      _error = error;
      _stackTrace = stack;
    });
    widget.onError?.call(error, stack);
  }
}

/// Default fallback UI untuk ErrorBoundary
class _DefaultErrorFallback extends StatelessWidget {
  final Object error;
  final VoidCallback onRetry;

  const _DefaultErrorFallback({
    required this.error,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AurexColors.rust.withValues(alpha: 0.1),
              ),
              child: const Icon(
                Icons.error_outline_rounded,
                size: 48,
                color: AurexColors.rust,
              ),
            ),
            const SizedBox(height: 24),
            Text(
              'Terjadi Kesalahan',
              style: AurexTypography.heading2,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 12),
            Text(
              'Maaf, terjadi kesalahan yang tidak terduga. Silakan coba lagi.',
              style: AurexTypography.bodyMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 32),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Coba Lagi'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AurexColors.olive,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Mixin untuk ConsumerStatefulWidget yang ingin error boundary otomatis.
///
/// Usage:
/// ```dart
/// class MyScreen extends ConsumerStatefulWidget {
///   const MyScreen({super.key});
///   @override
///   ConsumerState<MyScreen> createState() => _MyScreenState();
/// }
///
/// class _MyScreenState extends ConsumerState<MyScreen> with ErrorBoundaryMixin {
///   // ...
/// }
/// ```
mixin ErrorBoundaryMixin<T extends StatefulWidget> on State<T> {
  void reportError(Object error, StackTrace stack) {
    // Log ke Sentry atau service lain
    debugPrint('ErrorBoundaryMixin caught: $error\n$stack');
  }
}
