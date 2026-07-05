import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';

/// SPEC §3.11 — favorites.
class FavoritesScreen extends ConsumerStatefulWidget {
  const FavoritesScreen({super.key});

  @override
  ConsumerState<FavoritesScreen> createState() => _FavoritesScreenState();
}

class _FavoritesScreenState extends ConsumerState<FavoritesScreen> {
  bool _loading = true;
  Object? _error;
  List<Product> _items = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = ref.read(apiRepositoryProvider);
    try {
      final items = await api.favorites();
      if (!mounted) return;
      setState(() {
        _items = items;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        _loading = false;
      });
    }
  }

  Future<void> _remove(int productId) async {
    final api = ref.read(apiRepositoryProvider);
    try {
      await api.removeFavorite(productId);
      await _load();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('مفضلتي',
            style: Theme.of(context)
                .textTheme
                .titleLarge
                ?.copyWith(color: AppColors.maroon)),
      ),
      body: _body(),
    );
  }

  Widget _body() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, color: AppColors.danger, size: 40),
            const SizedBox(height: 12),
            Text(_error.toString(),
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.danger)),
            const SizedBox(height: 12),
            ElevatedButton(
                onPressed: _load, child: const Text('إعادة المحاولة')),
          ],
        ),
      );
    }
    if (_items.isEmpty) {
      return const Center(
        child: Padding(
          padding: EdgeInsets.all(32),
          child: Text('ما عندك مفضلة بعد — تصفحي التشكيلات ♡',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.mutedInk, fontSize: 15)),
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (_, i) => _FavoriteCard(
          product: _items[i],
          onTap: () => context.push('/product/${_items[i].id}'),
          onRemove: () => _remove(_items[i].id),
        ),
      ),
    );
  }
}

class _FavoriteCard extends StatelessWidget {
  const _FavoriteCard({
    required this.product,
    required this.onTap,
    required this.onRemove,
  });

  final Product product;
  final VoidCallback onTap;
  final VoidCallback onRemove;

  bool get _paused => product.badges
      .any((b) => b.contains('موقف') || b.toLowerCase().contains('paused'));

  @override
  Widget build(BuildContext context) {
    final price = product.salePrice != null
        ? formatSar(product.salePrice!)
        : (product.rentPrice != null
            ? 'تأجير ${formatSar(product.rentPrice!)}'
            : null);

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.roseSoft),
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(
                  width: 96,
                  height: 96,
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [AppColors.roseSoft, AppColors.goldLight],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(product.title,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                fontWeight: FontWeight.w700, fontSize: 14)),
                        const SizedBox(height: 2),
                        Text(product.storeName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                fontSize: 12, color: AppColors.mutedInk)),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            if (price != null)
                              Text(price,
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.maroon)),
                            const Spacer(),
                            if (product.rating != null) ...[
                              const Text('★',
                                  style: TextStyle(
                                      color: AppColors.gold, fontSize: 12)),
                              const SizedBox(width: 2),
                              Text(product.rating!.toString().toArabicDigits(),
                                  style: const TextStyle(fontSize: 11.5)),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                IconButton(
                  icon: const Icon(Icons.favorite, color: AppColors.maroon),
                  tooltip: 'إزالة من المفضلة',
                  onPressed: onRemove,
                ),
              ],
            ),
            if (_paused)
              Container(
                width: double.infinity,
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                color: AppColors.warnBg,
                child: const Text(
                  '⏸ البائعة موقفة الطلبات — بنبلغك أول ما تفتح 🔔',
                  style: TextStyle(
                      color: AppColors.warnInk,
                      fontSize: 12,
                      fontWeight: FontWeight.w600),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
