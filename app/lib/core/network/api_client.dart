import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Base URL of the Laravel API. Override at build time:
///   flutter run --dart-define=API_BASE_URL=https://api.anaqatuk.sa
const kApiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://10.0.2.2:8000/api', // Android emulator → host localhost
);

const _secureStorage = FlutterSecureStorage();
const _tokenKey = 'anaqatuk_token';

/// Reads/writes the Sanctum token in the platform keychain. SPEC §1.
final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());

class TokenStorage {
  Future<String?> read() => _secureStorage.read(key: _tokenKey);
  Future<void> write(String token) =>
      _secureStorage.write(key: _tokenKey, value: token);
  Future<void> clear() => _secureStorage.delete(key: _tokenKey);
}

/// A configured Dio that attaches the bearer token and surfaces the API's
/// Arabic error messages (422) as [ApiException].
final dioProvider = Provider<Dio>((ref) {
  final storage = ref.read(tokenStorageProvider);

  final dio = Dio(
    BaseOptions(
      baseUrl: kApiBaseUrl,
      headers: {'Accept': 'application/json'},
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
    ),
  );

  dio.interceptors.add(
    InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await storage.read();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (e, handler) {
        handler.reject(_toApiException(e));
      },
    ),
  );

  return dio;
});

DioException _toApiException(DioException e) {
  final data = e.response?.data;
  String? message;
  if (data is Map) {
    message = data['message'] as String? ??
        (data['errors'] is Map
            ? (data['errors'] as Map).values.first?.toString()
            : null);
  }
  return DioException(
    requestOptions: e.requestOptions,
    response: e.response,
    type: e.type,
    error: ApiException(
      message ?? 'حدث خطأ، حاولي مرة ثانية',
      statusCode: e.response?.statusCode,
    ),
  );
}

/// A user-facing error carrying the backend's Arabic message.
class ApiException implements Exception {
  const ApiException(this.message, {this.statusCode});
  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}
