import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:sentry_flutter/sentry_flutter.dart';
import 'request_tracker.dart';

/// Dio interceptor untuk end-to-end tracing.
///
/// Menambahkan X-Request-Id ke setiap request dan mengekstraknya
/// dari response untuk forwarding ke request berikutnya.
///
/// Juga mengirim request_id ke Sentry untuk correlation antara
/// crash/error di Flutter dengan log di backend.
class TracingInterceptor extends Interceptor {
  final RequestTracker _tracker;

  TracingInterceptor({RequestTracker? tracker})
      : _tracker = tracker ?? globalRequestTracker;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    // Forward request_id dari response sebelumnya (jika ada)
    final previousId = _tracker.requestId;
    if (previousId != null) {
      options.headers['X-Request-Id'] = previousId;

      // Tag Sentry scope dengan request_id untuk korelasi
      Sentry.configureScope((scope) {
        scope.setTag('request_id', previousId);
        scope.addBreadcrumb(Breadcrumb(
          message: 'API: ${options.method} ${options.path}',
          category: 'api',
          level: SentryLevel.info,
          data: {
            'request_id': previousId,
            'url': options.uri.toString(),
          },
        ));
      });
    }

    if (kDebugMode) {
      debugPrint(
        '[TRACE] ${options.method} ${options.path} '
        '→ request_id: ${_tracker.displayId}',
      );
    }

    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    // Ekstrak X-Request-Id dari response header
    final requestId = response.headers.value('X-Request-Id');
    if (requestId != null) {
      _tracker.setRequestId(requestId);

      // Tag Sentry scope
      Sentry.configureScope((scope) {
        scope.setTag('request_id', requestId);
        scope.addBreadcrumb(Breadcrumb(
          message: 'RESPONSE: ${response.statusCode}',
          category: 'api',
          level: SentryLevel.info,
          data: {
            'request_id': requestId,
            'status_code': response.statusCode,
            'url': response.realUri.toString(),
          },
        ));
      });

      if (kDebugMode) {
        debugPrint(
          '[TRACE] ${response.statusCode} ${response.requestOptions.path} '
          '← request_id: ${_tracker.displayId}',
        );
      }
    }

    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    // Tetap ekstrak request_id dari response error jika ada
    final requestId = err.response?.headers.value('X-Request-Id');
    if (requestId != null) {
      _tracker.setRequestId(requestId);

      // Kirim error ke Sentry dengan konteks request_id
      Sentry.configureScope((scope) {
        scope.setTag('request_id', requestId);
        scope.setContexts('dio_error', {
          'type': err.type.name,
          'status_code': err.response?.statusCode,
          'message': err.message,
        });
      });
    }

    if (kDebugMode) {
      debugPrint(
        '[TRACE] ERROR ${err.response?.statusCode} '
        '${err.requestOptions.path} '
        '← request_id: ${_tracker.displayId}',
      );
    }

    handler.next(err);
  }
}
