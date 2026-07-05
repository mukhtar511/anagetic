import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../core/network/api_client.dart';
import '../models/models.dart';

/// Thin wrapper over the REST API. One method per endpoint the app uses.
class ApiRepository {
  ApiRepository(this._dio);
  final Dio _dio;

  // --- Auth (SPEC §3.15) ---
  Future<void> requestOtp(String phone) =>
      _dio.post('/auth/otp/request', data: {'phone': phone});

  Future<({String token, AuthUser user})> verifyOtp(
      String phone, String code, int? regionId) async {
    final res = await _dio.post('/auth/otp/verify', data: {
      'phone': phone,
      'code': code,
      if (regionId != null) 'region_id': regionId,
    });
    final data = res.data as Map<String, dynamic>;
    return (
      token: data['token'] as String,
      user: AuthUser.fromJson(data['user'] as Map<String, dynamic>),
    );
  }

  Future<AuthUser> me() async {
    final res = await _dio.get('/me');
    return AuthUser.fromJson(_unwrap(res.data));
  }

  Future<void> logout() => _dio.post('/auth/logout');

  // --- Catalog (SPEC §3.1/§3.2) ---
  Future<List<Region>> regions() async {
    final res = await _dio.get('/regions');
    return _list(res.data).map((e) => Region.fromJson(e)).toList();
  }

  Future<List<Product>> products({
    String? category,
    int? regionId,
    int? nearRegionId,
    num? min,
    num? max,
    String? sort,
    String? tag,
    String? q,
  }) async {
    final res = await _dio.get('/products', queryParameters: {
      if (category != null) 'category': category,
      if (regionId != null) 'region_id': regionId,
      if (nearRegionId != null) 'near_region_id': nearRegionId,
      if (min != null) 'min': min,
      if (max != null) 'max': max,
      if (sort != null) 'sort': sort,
      if (tag != null) 'tag': tag,
      if (q != null && q.isNotEmpty) 'q': q,
    });
    return _list(res.data).map((e) => Product.fromJson(e)).toList();
  }

  Future<Product> product(int id) async {
    final res = await _dio.get('/products/$id');
    return Product.fromJson(_unwrap(res.data));
  }

  Future<List<Product>> featured({int? regionId}) async {
    final res = await _dio.get('/featured',
        queryParameters: {if (regionId != null) 'region': regionId});
    return _list(res.data).map((e) => Product.fromJson(e)).toList();
  }

  // --- Cart & checkout (SPEC §3.5) ---
  Future<void> addToCart(Map<String, dynamic> item) =>
      _dio.post('/cart/items', data: item);

  Future<Map<String, dynamic>> cart() async {
    final res = await _dio.get('/cart');
    return res.data as Map<String, dynamic>;
  }

  Future<List<OrderModel>> checkout(String paymentMethod, {String? coupon}) async {
    final res = await _dio.post('/checkout', data: {
      'payment_method': paymentMethod,
      if (coupon != null) 'coupon_code': coupon,
    });
    return _list(res.data['orders'] ?? res.data).map((e) => OrderModel.fromJson(e)).toList();
  }

  // --- Orders (SPEC §3.6) ---
  Future<List<OrderModel>> orders({String? group}) async {
    final res = await _dio.get('/orders',
        queryParameters: {if (group != null) 'status': group});
    return _list(res.data).map((e) => OrderModel.fromJson(e)).toList();
  }

  Future<void> requestReturn(int orderId, String reason) =>
      _dio.post('/orders/$orderId/return', data: {'reason': reason});

  Future<void> cancelOrder(int orderId) => _dio.post('/orders/$orderId/cancel');

  // --- Wallet (SPEC §3.7) ---
  Future<({num balance, List<WalletEntry> entries})> wallet() async {
    final res = await _dio.get('/wallet');
    final data = res.data as Map<String, dynamic>;
    return (
      balance: (data['balance'] ?? 0) as num,
      entries: _list(data['ledger'] ?? data['entries'] ?? [])
          .map((e) => WalletEntry.fromJson(e))
          .toList(),
    );
  }

  Future<void> withdraw(num amount) =>
      _dio.post('/wallet/withdraw', data: {'amount': amount});

  // --- Notifications (SPEC §3.12) ---
  Future<({int unread, List<AppNotification> items})> notifications() async {
    final res = await _dio.get('/notifications');
    final data = res.data as Map<String, dynamic>;
    return (
      unread: (data['unread_count'] ?? 0) as int,
      items: _list(data['data'] ?? data['items'] ?? [])
          .map((e) => AppNotification.fromJson(e))
          .toList(),
    );
  }

  Future<void> readAllNotifications() => _dio.post('/notifications/read-all');

  // --- Smart requests (SPEC §3.9) ---
  Future<void> createSmartRequest(Map<String, dynamic> body) =>
      _dio.post('/smart-requests', data: body);

  Future<List<Map<String, dynamic>>> smartRequests() async {
    final res = await _dio.get('/smart-requests');
    return _list(res.data);
  }

  Future<void> acceptOffer(int offerId) => _dio.post('/offers/$offerId/accept');

  // --- Ratings (SPEC §3.13) ---
  Future<void> rateOrder(int orderId, int stars, List<String> chips, String? text) =>
      _dio.post('/orders/$orderId/rating',
          data: {'stars': stars, 'chips': chips, if (text != null) 'text': text});

  // --- Stores / designer page (SPEC §3.3) ---
  Future<List<Map<String, dynamic>>> stores() async {
    final res = await _dio.get('/stores');
    return _list(res.data);
  }

  Future<Map<String, dynamic>> store(int id) async {
    final res = await _dio.get('/stores/$id');
    return _unwrap(res.data);
  }

  // --- Favorites (SPEC §3.11) ---
  Future<List<Product>> favorites() async {
    final res = await _dio.get('/favorites');
    return _list(res.data).map((e) => Product.fromJson(e)).toList();
  }

  Future<void> addFavorite(int productId, {bool notifyWhenOpen = false}) =>
      _dio.post('/favorites',
          data: {'product_id': productId, 'notify_when_open': notifyWhenOpen});

  Future<void> removeFavorite(int productId) =>
      _dio.delete('/favorites/$productId');

  // --- Chat (SPEC §3.10) ---
  Future<List<Map<String, dynamic>>> conversations() async {
    final res = await _dio.get('/conversations');
    return _list(res.data);
  }

  Future<List<Map<String, dynamic>>> messages(int conversationId) async {
    final res = await _dio.get('/conversations/$conversationId/messages');
    return _list(res.data);
  }

  Future<Map<String, dynamic>> sendMessage(int conversationId, String body) async {
    final res = await _dio.post('/conversations/$conversationId/messages',
        data: {'body': body});
    return _unwrap(res.data);
  }

  Future<void> acceptAddonOffer(int offerId) =>
      _dio.post('/addon-offers/$offerId/accept');

  Future<void> rejectAddonOffer(int offerId) =>
      _dio.post('/addon-offers/$offerId/reject');

  // --- Seller: listing (SPEC §3.4) ---
  Future<Map<String, dynamic>> aiFill() async {
    final res = await _dio.post('/listings/ai-fill');
    return _unwrap(res.data);
  }

  Future<Map<String, dynamic>> createProduct(Map<String, dynamic> body) async {
    final res = await _dio.post('/products', data: body);
    return _unwrap(res.data);
  }

  Future<void> deleteProduct(int id) => _dio.delete('/products/$id');

  Future<void> buyFeatured(int productId, String scope, int days, String payVia) =>
      _dio.post('/products/$productId/featured',
          data: {'scope': scope, 'days': days, 'pay_via': payVia});

  // --- Seller: dashboard & store (SPEC §3.8) ---
  Future<Map<String, dynamic>> sellerDashboard() async {
    final res = await _dio.get('/seller/dashboard');
    return _unwrap(res.data);
  }

  Future<void> confirmDelivery(int orderId, String code) =>
      _dio.post('/seller/orders/$orderId/confirm-delivery', data: {'code': code});

  Future<void> confirmRentalSound(int bookingId) =>
      _dio.post('/seller/rentals/$bookingId/sound');

  Future<void> openRentalDispute(int bookingId) =>
      _dio.post('/seller/rentals/$bookingId/dispute');

  Future<void> updateStore(Map<String, dynamic> body) =>
      _dio.put('/store', data: body);

  Future<List<Map<String, dynamic>>> sellerCoupons() async {
    final res = await _dio.get('/seller/coupons');
    return _list(res.data);
  }

  Future<void> createSellerCoupon(Map<String, dynamic> body) =>
      _dio.post('/seller/coupons', data: body);

  Future<void> updateSellerCoupon(int id, Map<String, dynamic> body) =>
      _dio.patch('/seller/coupons/$id', data: body);

  Future<void> deleteSellerCoupon(int id) =>
      _dio.delete('/seller/coupons/$id');

  Future<List<Map<String, dynamic>>> sellerSmartRequests() async {
    final res = await _dio.get('/seller/smart-requests');
    return _list(res.data);
  }

  Future<void> submitSmartOffer(int requestId, num price, String? message) =>
      _dio.post('/smart-requests/$requestId/offers',
          data: {'price': price, if (message != null) 'message': message});

  // --- Helpers ---
  List<Map<String, dynamic>> _list(dynamic data) {
    final raw = data is Map && data.containsKey('data') ? data['data'] : data;
    return (raw as List).cast<Map<String, dynamic>>();
  }

  Map<String, dynamic> _unwrap(dynamic data) =>
      (data is Map && data.containsKey('data') ? data['data'] : data)
          as Map<String, dynamic>;
}

final apiRepositoryProvider = Provider<ApiRepository>(
  (ref) => ApiRepository(ref.read(dioProvider)),
);
