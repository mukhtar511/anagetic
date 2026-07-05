import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';
import '../../providers/auth_provider.dart';

/// SPEC §3.1 — home / marketplace.
class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _Category {
  const _Category(this.code, this.label);
  final String? code;
  final String label;
}

const _categories = <_Category>[
  _Category(null, 'الكل'),
  _Category('abaya', '🖤 عبايات'),
  _Category('dress', '👗 فساتين'),
  _Category('care', '🌿 أدوات عناية'),
  _Category('acc', '💍 إكسسوارات'),
  _Category('nobrand', '📦 بدون براند'),
  _Category('used', '♻️ المستعمل'),
];

const _sortOptions = <MapEntry<String, String>>[
  MapEntry('new', 'الأحدث'),
  MapEntry('price_asc', 'السعر من الأقل'),
  MapEntry('price_desc', 'من الأعلى'),
  MapEntry('rating', 'الأعلى تقييمًا'),
];

class _HomeScreenState extends ConsumerState<HomeScreen> {
  final _searchCtrl = TextEditingController();
  String? _category;
  String _sort = 'new';
  String _mode = 'buy'; // buy | rent
  String _query = '';

  bool _loading = true;
  Object? _error;
  List<Product> _products = const [];
  List<Product> _featured = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  int? get _regionId => ref.read(authProvider).user?.regionId;

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = ref.read(apiRepositoryProvider);
    try {
      final results = await Future.wait([
        api.products(
          category: _category,
          nearRegionId: _regionId,
          sort: _sort,
          tag: _mode == 'rent' ? 'rent' : null,
          q: _query.isEmpty ? null : _query,
        ),
        api.featured(regionId: _regionId),
      ]);
      if (!mounted) return;
      setState(() {
        _products = results[0];
        _featured = results[1];
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('أناقتك',
            style: Theme.of(context)
                .textTheme
                .titleLarge
                ?.copyWith(color: AppColors.maroon)),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none),
            tooltip: 'إشعاراتي',
            onPressed: () => context.push('/notifications'),
          ),
          IconButton(
            icon: const Icon(Icons.favorite_border),
            tooltip: 'المفضلة',
            onPressed: () => context.push('/favorites'),
          ),
          IconButton(
            icon: const Icon(Icons.shopping_bag_outlined),
            tooltip: 'السلة',
            onPressed: () => context.push('/cart'),
          ),
          IconButton(
            icon: const Icon(Icons.person_outline),
            tooltip: 'حسابي',
            onPressed: () => context.push('/account'),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            const _PromoBar(),
            Padding(
              padding: const EdgeInsets.all(16),
              child: _searchField(),
            ),
            _categoryChips(),
            const SizedBox(height: 12),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: _heroBand(),
            ),
            const SizedBox(height: 16),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: _smartRequestBanner(),
            ),
            const SizedBox(height: 20),
            _featuredStrip(),
            const SizedBox(height: 8),
            _sortRow(),
            _productsSection(),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  Widget _searchField() {
    return TextField(
      controller: _searchCtrl,
      textInputAction: TextInputAction.search,
      onSubmitted: (v) {
        _query = v.trim();
        _load();
      },
      decoration: InputDecoration(
        hintText: 'ابحثي عن عباية، فستان، مصممة...',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: IconButton(
          icon: const Icon(Icons.arrow_circle_left_outlined),
          onPressed: () {
            _query = _searchCtrl.text.trim();
            _load();
          },
        ),
      ),
    );
  }

  Widget _categoryChips() {
    return SizedBox(
      height: 44,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _categories.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) {
          final c = _categories[i];
          final selected = c.code == _category;
          return ChoiceChip(
            label: Text(c.label),
            selected: selected,
            onSelected: (_) {
              setState(() => _category = c.code);
              _load();
            },
            selectedColor: AppColors.maroon,
            labelStyle: TextStyle(
              color: selected ? Colors.white : AppColors.ink,
              fontWeight: FontWeight.w600,
            ),
            backgroundColor: AppColors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(99),
              side: const BorderSide(color: AppColors.roseSoft),
            ),
          );
        },
      ),
    );
  }

  Widget _heroBand() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: const LinearGradient(
          colors: [AppColors.maroon, AppColors.maroonDeep],
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('سوق المصممات السعوديات',
              style: TextStyle(color: AppColors.goldLight, fontSize: 13)),
          const SizedBox(height: 8),
          const Text(
            'كل مصممة لها متجرها.. وأنتِ لكِ أناقتك',
            style: TextStyle(
                color: Colors.white,
                fontSize: 22,
                fontWeight: FontWeight.w700,
                height: 1.4),
          ),
          const SizedBox(height: 16),
          _buyRentToggle(),
        ],
      ),
    );
  }

  Widget _buyRentToggle() {
    Widget seg(String value, String label) {
      final selected = _mode == value;
      return Expanded(
        child: GestureDetector(
          onTap: () {
            setState(() => _mode = value);
            _load();
          },
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 10),
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: selected ? AppColors.white : Colors.transparent,
              borderRadius: BorderRadius.circular(99),
            ),
            child: Text(
              label,
              style: TextStyle(
                color: selected ? AppColors.maroon : Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      );
    }

    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(99),
      ),
      child: Row(children: [seg('buy', 'شراء'), seg('rent', 'تأجير')]),
    );
  }

  Widget _smartRequestBanner() {
    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/smart-request'),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: AppColors.warnBg,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.goldLight),
        ),
        child: const Row(
          children: [
            Text('🪄', style: TextStyle(fontSize: 26)),
            SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('الطلب الذكي',
                      style: TextStyle(
                          fontWeight: FontWeight.w700,
                          color: AppColors.warnInk)),
                  SizedBox(height: 4),
                  Text('ما تبين تدورين؟ اكتبي طلبك — والبائعات يتنافسن عليه',
                      style: TextStyle(fontSize: 13, color: AppColors.ink)),
                ],
              ),
            ),
            Icon(Icons.arrow_back, color: AppColors.warnInk),
          ],
        ),
      ),
    );
  }

  Widget _featuredStrip() {
    if (_featured.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 16),
          child: Text('⭐ المميزة',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        ),
        const SizedBox(height: 10),
        SizedBox(
          height: 210,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            itemCount: _featured.length,
            separatorBuilder: (_, __) => const SizedBox(width: 12),
            itemBuilder: (_, i) => SizedBox(
              width: 170,
              child: _ProductCard(
                product: _featured[i],
                featured: true,
                onTap: () => context.push('/product/${_featured[i].id}'),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _sortRow() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
      child: Row(
        children: [
          const Text('التشكيلات',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          const Spacer(),
          const Icon(Icons.sort, size: 18, color: AppColors.mutedInk),
          const SizedBox(width: 6),
          DropdownButton<String>(
            value: _sort,
            underline: const SizedBox.shrink(),
            items: [
              for (final s in _sortOptions)
                DropdownMenuItem(value: s.key, child: Text(s.value)),
            ],
            onChanged: (v) {
              if (v == null) return;
              setState(() => _sort = v);
              _load();
            },
          ),
        ],
      ),
    );
  }

  Widget _productsSection() {
    if (_loading) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 48),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (_error != null) {
      return Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
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
    if (_products.isEmpty) {
      return const Padding(
        padding: EdgeInsets.all(32),
        child: Center(
          child: Text(
            'ما فيه منتجات تطابق الفلتر — جرّبي توسيع نطاق السعر أو المنطقة.',
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.mutedInk),
          ),
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
      itemBuilder: (_, i) => _ProductCard(
        product: _products[i],
        onTap: () => context.push('/product/${_products[i].id}'),
      ),
    );
  }
}

class _PromoBar extends StatelessWidget {
  const _PromoBar();

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      color: AppColors.maroonDeep,
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
      child: const Text(
        '🚚 شحن مجاني للطلبات فوق ٣٠٠ ر.س — واستأجري فستان المناسبة بدل ما تشترينه ✨',
        textAlign: TextAlign.center,
        style: TextStyle(color: Colors.white, fontSize: 12.5),
      ),
    );
  }
}

class _ProductCard extends StatelessWidget {
  const _ProductCard({
    required this.product,
    required this.onTap,
    this.featured = false,
  });

  final Product product;
  final VoidCallback onTap;
  final bool featured;

  @override
  Widget build(BuildContext context) {
    final scopeBadge = product.nearRegion ? '📍' : '🌍';
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
            Stack(
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
                if (featured)
                  Positioned(
                    top: 8,
                    right: 8,
                    child: _badge('⭐ مميز', AppColors.gold),
                  ),
                Positioned(
                  top: 8,
                  left: 8,
                  child: _badge(scopeBadge, Colors.black54),
                ),
              ],
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 13.5),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    product.storeName,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style:
                        const TextStyle(fontSize: 11.5, color: AppColors.mutedInk),
                  ),
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
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      if (product.rating != null) ...[
                        const Text('★',
                            style: TextStyle(color: AppColors.gold, fontSize: 12)),
                        const SizedBox(width: 2),
                        Text(product.rating!.toString().toArabicDigits(),
                            style: const TextStyle(fontSize: 11.5)),
                        const SizedBox(width: 6),
                      ],
                      if (product.region != null)
                        Expanded(
                          child: Text(product.region!,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                  fontSize: 11, color: AppColors.mutedInk)),
                        ),
                    ],
                  ),
                  if (product.nearRegion) ...[
                    const SizedBox(height: 6),
                    _badge('📍 قريبة منك', AppColors.green),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _badge(String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(99),
      ),
      child: Text(text,
          style: const TextStyle(color: Colors.white, fontSize: 10.5)),
    );
  }
}
