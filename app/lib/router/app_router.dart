import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../features/account/account_screen.dart';
import '../features/auth/login_screen.dart';
import '../features/cart/cart_screen.dart';
import '../features/chat/chat_screen.dart';
import '../features/chat/conversations_screen.dart';
import '../features/designer/designer_screen.dart';
import '../features/favorites/favorites_screen.dart';
import '../features/home/home_screen.dart';
import '../features/listing/listing_screen.dart';
import '../features/notifications/notifications_screen.dart';
import '../features/orders/orders_screen.dart';
import '../features/policies/policies_screen.dart';
import '../features/product/product_screen.dart';
import '../features/seller/seller_dashboard_screen.dart';
import '../features/smart_request/smart_request_screen.dart';
import '../providers/auth_provider.dart';

/// Gated routes require login; the gateway redirects to OTP then resumes. SPEC §2.
const _gated = {
  '/orders', '/account', '/smart-request', '/cart',
  '/favorites', '/chat', '/seller', '/listing',
};

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      if (auth.bootstrapping) return null;
      final loc = state.matchedLocation;
      final needsAuth = _gated.any((g) => loc.startsWith(g));
      if (needsAuth && !auth.isAuthenticated) {
        return '/login?from=${Uri.encodeComponent(loc)}';
      }
      if (loc == '/login' && auth.isAuthenticated) {
        final from = state.uri.queryParameters['from'];
        return from ?? '/';
      }
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (_, __) => const HomeScreen()),
      GoRoute(path: '/login', builder: (_, s) => LoginScreen(from: s.uri.queryParameters['from'])),
      GoRoute(
        path: '/product/:id',
        builder: (_, s) => ProductScreen(productId: int.parse(s.pathParameters['id']!)),
      ),
      GoRoute(
        path: '/store/:id',
        builder: (_, s) => DesignerScreen(storeId: int.parse(s.pathParameters['id']!)),
      ),
      GoRoute(path: '/cart', builder: (_, __) => const CartScreen()),
      GoRoute(path: '/orders', builder: (_, __) => const OrdersScreen()),
      GoRoute(path: '/account', builder: (_, __) => const AccountScreen()),
      GoRoute(path: '/smart-request', builder: (_, __) => const SmartRequestScreen()),
      GoRoute(path: '/notifications', builder: (_, __) => const NotificationsScreen()),
      GoRoute(path: '/policies', builder: (_, __) => const PoliciesScreen()),
      GoRoute(path: '/favorites', builder: (_, __) => const FavoritesScreen()),
      GoRoute(path: '/chat', builder: (_, __) => const ConversationsScreen()),
      GoRoute(
        path: '/chat/:id',
        builder: (_, s) => ChatScreen(conversationId: int.parse(s.pathParameters['id']!)),
      ),
      GoRoute(path: '/seller', builder: (_, __) => const SellerDashboardScreen()),
      GoRoute(path: '/listing', builder: (_, __) => const ListingScreen()),
    ],
  );
});
