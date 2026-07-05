import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';

/// SPEC §3.3 — designer / store page.
class DesignerScreen extends ConsumerStatefulWidget {
  const DesignerScreen({super.key, required this.storeId});
  final int storeId;

  @override
  ConsumerState<DesignerScreen> createState() => _DesignerScreenState();
}

class _DesignerScreenState extends ConsumerState<DesignerScreen> {
  final _productsKey = GlobalKey();

  bool _loading = true;
  Object? _error;
  Map<String, dynamic> _store = const {};
  List<Product> _products = const [];

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
      final store = await api.store(widget.storeId);
      if (!mounted) return;
      final rawProducts = (store['products'] as List?) ?? const [];
      setState(() {
        _store = store;
        _products = rawProducts
            .whereType<Map<String, dynamic>>()
            .map((e) => Product.fromJson(e))
            .toList();
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

  String get _name => (_store['name'] ?? 'المتجر') as String;

  String? _str(List<String> keys) {
    for (final k in keys) {
      final v = _store[k];
      if (v is String && v.trim().isNotEmpty) return v;
    }
    return null;
  }

  num? _num(List<String> keys) {
    for (final k in keys) {
      final v = _store[k];
      if (v is num) return v;
    }
    return null;
  }

  String get _status =>
      (_store['status'] ?? _store['store_status'] ?? 'open') as String;

  void _scrollToProducts() {
    final ctx = _productsKey.currentContext;
    if (ctx != null) {
      Scrollable.ensureVisible(ctx,
          duration: const Duration(milliseconds: 400), alignment: 0.05);
    }
  }

  void _messageDesigner() {
    final convId = _store['conversation_id'] ?? _store['conversationId'];
    if (convId is int) {
      context.push('/chat/$convId');
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('افتحي محادثة من صفحة المنتج')),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(_loading ? 'المتجر' : _name,
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
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          _header(),
          const SizedBox(height: 16),
          _stats(),
          if (_status != 'open') ...[
            const SizedBox(height: 16),
            _statusNote(),
          ],
          const SizedBox(height: 20),
          Padding(
            key: _productsKey,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: const Text('التشكيلة',
                style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          ),
          const SizedBox(height: 12),
          _productsGrid(),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _header() {
    final tagline = _str(['tagline', 'bio', 'headline']);
    final region = _str(['region', 'region_name']);
    final bio = _str(['bio', 'about', 'description']);
    final subtitle = [tagline, region].whereType<String>().join(' · ');
    final letter = _name.isNotEmpty ? _name.characters.first : '؟';

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [AppColors.maroon, AppColors.maroonDeep],
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 32,
                backgroundColor: Colors.white.withValues(alpha: 0.18),
                child: Text(letter,
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 26,
                        fontWeight: FontWeight.w700)),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(_name,
                        style: const TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.w700)),
                    if (subtitle.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(subtitle,
                          style: const TextStyle(
                              color: AppColors.goldLight, fontSize: 13)),
                    ],
                  ],
                ),
              ),
            ],
          ),
          if (bio != null && bio != tagline) ...[
            const SizedBox(height: 14),
            Text(bio,
                style: const TextStyle(
                    color: Colors.white, fontSize: 13.5, height: 1.5)),
          ],
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: ElevatedButton(
                  onPressed: _scrollToProducts,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.white,
                    foregroundColor: AppColors.maroon,
                  ),
                  child: const Text('تسوّقي التشكيلة'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: OutlinedButton(
                  onPressed: _messageDesigner,
                  style: OutlinedButton.styleFrom(
                    foregroundColor: Colors.white,
                    side: const BorderSide(color: Colors.white70),
                  ),
                  child: const Text('💬 راسلي المصممة'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _stats() {
    final rating = _num(['rating_avg', 'rating']);
    final completed = _num(['completed_orders', 'orders_completed']);
    final commitment = _num(['commitment', 'commitment_rate', 'on_time_rate']);
    final joined = _num(['joined_year', 'joined', 'year']);

    final tiles = <_Stat>[
      _Stat('متوسط التقييم',
          rating != null ? rating.toString().toArabicDigits() : '—'),
      _Stat('طلب مكتمل',
          completed != null ? completed.toStringAsFixed(0).toArabicDigits() : '—'),
      commitment != null
          ? _Stat('التزام بمدة التنفيذ',
              '${commitment.toStringAsFixed(0).toArabicDigits()}٪')
          : _Stat('سنة الانضمام',
              joined != null ? joined.toStringAsFixed(0).toArabicDigits() : '—'),
      _Stat('سنة الانضمام',
          joined != null ? joined.toStringAsFixed(0).toArabicDigits() : '—'),
    ];

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: GridView.count(
        crossAxisCount: 2,
        shrinkWrap: true,
        physics: const NeverScrollableScrollPhysics(),
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 1.7,
        children: [for (final t in tiles) _StatTile(stat: t)],
      ),
    );
  }

  Widget _statusNote() {
    final paused = _status == 'paused';
    final text = paused
        ? '⏸ هذه المصممة موقفة الطلبات حاليًا — بنبلغك أول ما تفتح 🔔'
        : '🔒 المتجر مقفل مؤقتًا';
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.warnBg,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.goldLight),
        ),
        child: Text(text,
            style: const TextStyle(
                color: AppColors.warnInk, fontWeight: FontWeight.w600)),
      ),
    );
  }

  Widget _productsGrid() {
    if (_products.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(32),
        child: Center(
          child: Text('ما فيه منتجات في هذه التشكيلة بعد.',
              textAlign: TextAlign.center,
              style: TextStyle(color: AppColors.mutedInk)),
        ),
      );
    }
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(horizontal: 16),
      gridDelegate: const SliverGridDelegateWithMaxCrossAxisExtent(
        maxCrossAxisExtent: 220,
        mainAxisSpacing: 12,
        crossAxisSpacing: 12,
        childAspectRatio: 0.62,
      ),
      itemCount: _products.length,
      itemBuilder: (_, i) => _DesignerProductCard(
        product: _products[i],
        onTap: () => context.push('/product/${_products[i].id}'),
      ),
    );
  }
}

class _Stat {
  const _Stat(this.label, this.value);
  final String label;
  final String value;
}

class _StatTile extends StatelessWidget {
  const _StatTile({required this.stat});
  final _Stat stat;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(stat.value,
              style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 20,
                  color: AppColors.maroon)),
          const SizedBox(height: 2),
          Text(stat.label,
              style: const TextStyle(fontSize: 12, color: AppColors.ink)),
          const SizedBox(height: 4),
          const Text('✓ موثّق من أناقتك',
              style: TextStyle(fontSize: 10.5, color: AppColors.green)),
        ],
      ),
    );
  }
}

class _DesignerProductCard extends StatelessWidget {
  const _DesignerProductCard({required this.product, required this.onTap});
  final Product product;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
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
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const AspectRatio(
              aspectRatio: 1,
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
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(product.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontWeight: FontWeight.w700, fontSize: 13.5)),
                  const SizedBox(height: 6),
                  if (product.salePrice != null)
                    Text(formatSar(product.salePrice!),
                        style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            color: AppColors.maroon)),
                  if (product.rentPrice != null)
                    Text('تأجير ${formatSar(product.rentPrice!)}',
                        style: const TextStyle(
                            fontSize: 11.5, color: AppColors.green)),
                  if (product.rating != null) ...[
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        const Text('★',
                            style: TextStyle(color: AppColors.gold, fontSize: 12)),
                        const SizedBox(width: 2),
                        Text(product.rating!.toString().toArabicDigits(),
                            style: const TextStyle(fontSize: 11.5)),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
