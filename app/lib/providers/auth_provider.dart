import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/network/api_client.dart';
import '../data/api_repository.dart';
import '../models/models.dart';

/// Auth state: null user = guest. The gateway (router) redirects gated
/// screens to OTP login and resumes to the intended destination. SPEC §2/§3.15.
class AuthState {
  const AuthState({this.user, this.loading = false, this.bootstrapping = true});
  final AuthUser? user;
  final bool loading;
  final bool bootstrapping;

  bool get isAuthenticated => user != null;

  AuthState copyWith({AuthUser? user, bool? loading, bool? bootstrapping, bool clearUser = false}) =>
      AuthState(
        user: clearUser ? null : (user ?? this.user),
        loading: loading ?? this.loading,
        bootstrapping: bootstrapping ?? this.bootstrapping,
      );
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier(this._ref) : super(const AuthState()) {
    _bootstrap();
  }

  final Ref _ref;
  ApiRepository get _api => _ref.read(apiRepositoryProvider);
  TokenStorage get _storage => _ref.read(tokenStorageProvider);

  Future<void> _bootstrap() async {
    final token = await _storage.read();
    if (token == null) {
      state = state.copyWith(bootstrapping: false);
      return;
    }
    try {
      final user = await _api.me();
      state = state.copyWith(user: user, bootstrapping: false);
    } catch (_) {
      await _storage.clear();
      state = state.copyWith(bootstrapping: false, clearUser: true);
    }
  }

  Future<void> requestOtp(String phone) => _api.requestOtp(phone);

  Future<void> verifyOtp(String phone, String code, int? regionId) async {
    state = state.copyWith(loading: true);
    try {
      final res = await _api.verifyOtp(phone, code, regionId);
      await _storage.write(res.token);
      state = state.copyWith(user: res.user, loading: false);
    } catch (e) {
      state = state.copyWith(loading: false);
      rethrow;
    }
  }

  Future<void> logout() async {
    try {
      await _api.logout();
    } catch (_) {}
    await _storage.clear();
    state = state.copyWith(clearUser: true);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>(
  (ref) => AuthNotifier(ref),
);
