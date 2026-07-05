import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';

/// SPEC §3.2 — product detail.
class ProductScreen extends ConsumerStatefulWidget {
  final int productId;
  const ProductScreen({super.key, required this.productId});

  @override
  ConsumerState<ProductScreen> createState() => _ProductScreenState();
}

const _modeLabels = <String, String>{
  'ready': 'شراء بدون تعديلات',
  'custom': 'شراء بتعديل المقاسات',
  'rent': 'تأجير',
};

const _measurementFields = <String, String>{
  'shoulder': 'الكتف',
  'bust': 'محيط الصدر',
  'waist': 'محيط الخصر',
  'hip': 'محيط الورك',
  'arm': 'محيط العضد',
  'sleeve': 'طول الكم',
  'length': 'الطول الكامل',
};

class _ProductScreenState extends ConsumerState<ProductScreen> {
  bool _loading = true;
  Object? _error;
  Product? _product;

  String? _mode;
  String? _color;
  final Set<int> _addonIds = {};
  final Map<String, TextEditingController> _measureCtrls = {
    for (final k in _measurementFields.keys) k: TextEditingController(),
  };
  final _notesCtrl = TextEditingController();
  DateTime? _occasionDate;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    for (final c in _measureCtrls.values) {
      c.dispose();
    }
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final p = await ref.read(apiRepositoryProvider).product(widget.productId);
      if (!mounted) return;
      setState(() {
        _product = p;
        _mode = p.modes.isNotEmpty ? p.modes.first.type : null;
        _color = p.colors
            .cast<ProductColor?>()
            .firstWhere((c) => c?.inStock ?? false, orElse: () => null)
            ?.name;
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

  ProductMode? get _selectedMode {
    final p = _product;
    if (p == null || _mode == null) return null;
    for (final m in p.modes) {
      if (m.type == _mode) return m;
    }
    return null;
  }

  num get _basePrice => _selectedMode?.price ?? 0;

  num get _deposit => _mode == 'rent' ? (_selectedMode?.deposit ?? 0) : 0;

  num get _addonsTotal {
    final p = _product;
    if (p == null) return 0;
    num sum = 0;
    for (final a in p.addons) {
      if (_addonIds.contains(a.id)) sum += a.price;
    }
    return sum;
  }

  num get _total => _basePrice + _addonsTotal + _deposit;

  String _errorMessage(Object e) {
    if (e is DioException && e.error is ApiException) {
      return (e.error as ApiException).message;
    }
    if (e is ApiException) return e.message;
    if (e is DioException) return e.message ?? 'حدث خطأ، حاولي مرة ثانية';
    return 'حدث خطأ، حاولي مرة ثانية';
  }

  Future<void> _addToCart() async {
    final p = _product;
    if (p == null || _mode == null) return;
    setState(() => _submitting = true);
    try {
      final measurements = <String, dynamic>{};
      if (_mode == 'custom') {
        for (final e in _measureCtrls.entries) {
          final v = e.value.text.trim();
          if (v.isNotEmpty) measurements[e.key] = num.tryParse(v) ?? v;
        }
        if (_notesCtrl.text.trim().isNotEmpty) {
          measurements['notes'] = _notesCtrl.text.trim();
        }
      }
      await ref.read(apiRepositoryProvider).addToCart({
        'product_id': p.id,
        'mode': _mode,
        if (_color != null) 'color': _color,
        'qty': 1,
        'addon_ids': _addonIds.toList(),
        if (measurements.isNotEmpty) 'measurements': measurements,
        if (_occasionDate != null)
          'occasion_date':
              _occasionDate!.toIso8601String().split('T').first,
      });
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('✓ أُضيفت للسلة'),
          action: SnackBarAction(
            label: 'السلة',
            onPressed: () => context.push('/cart'),
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(_errorMessage(e))));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('أناقتك')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.error_outline,
                  color: AppColors.danger, size: 40),
              const SizedBox(height: 12),
              Text(_error.toString(),
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.danger)),
              const SizedBox(height: 12),
              ElevatedButton(
                  onPressed: _load, child: const Text('إعادة المحاولة')),
            ],
          ),
        ),
      );
    }
    final p = _product!;
    return ListView(
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
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (p.code != null)
                Text(p.code!,
                    style: const TextStyle(
                        color: AppColors.mutedInk, fontSize: 12)),
              const SizedBox(height: 4),
              Text('${p.storeName} — موثّقة ✓',
                  style: const TextStyle(
                      color: AppColors.green, fontWeight: FontWeight.w600)),
              const SizedBox(height: 6),
              Text(p.title,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      color: AppColors.ink, fontWeight: FontWeight.w700)),
              const SizedBox(height: 6),
              if (p.rating != null)
                Row(
                  children: [
                    const Text('★', style: TextStyle(color: AppColors.gold)),
                    const SizedBox(width: 4),
                    Text(p.rating!.toString().toArabicDigits()),
                  ],
                ),
              const SizedBox(height: 20),
              _sectionTitle('اختاري النمط'),
              _modeSelector(p),
              if (_mode == 'custom') ...[
                const SizedBox(height: 20),
                _sectionTitle('مقاساتك بالتفصيل'),
                _measurements(),
              ],
              if (_mode == 'rent') ...[
                const SizedBox(height: 20),
                _sectionTitle('📅 حجز التأجير'),
                _rentPanel(),
              ],
              if (p.colors.isNotEmpty) ...[
                const SizedBox(height: 20),
                _sectionTitle('🎨 اختاري اللون'),
                _colorsRow(p),
              ],
              const SizedBox(height: 20),
              _sectionTitle('إضافات البائعة'),
              _addonsSection(p),
              const SizedBox(height: 20),
              _contactRow(p),
              const SizedBox(height: 20),
              _priceSummary(),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _submitting ? null : _addToCart,
                  child: _submitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('👜 أضيفي للسلة'),
                ),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ],
    );
  }

  Widget _sectionTitle(String t) => Padding(
        padding: const EdgeInsets.only(bottom: 10),
        child: Text(t,
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
      );

  Widget _modeSelector(Product p) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: [
        for (final m in p.modes)
          ChoiceChip(
            label: Text(
                '${_modeLabels[m.type] ?? m.type} · ${formatSar(m.price)}'),
            selected: _mode == m.type,
            onSelected: (_) => setState(() => _mode = m.type),
            selectedColor: AppColors.maroon,
            labelStyle: TextStyle(
              color: _mode == m.type ? Colors.white : AppColors.ink,
              fontWeight: FontWeight.w600,
            ),
            backgroundColor: AppColors.white,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
              side: const BorderSide(color: AppColors.roseSoft),
            ),
          ),
      ],
    );
  }

  Widget _measurements() {
    return Column(
      children: [
        for (final e in _measurementFields.entries)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: TextField(
              controller: _measureCtrls[e.key],
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              inputFormatters: [
                FilteringTextInputFormatter.allow(RegExp(r'[0-9.]')),
              ],
              decoration: InputDecoration(
                labelText: e.value,
                suffixText: 'سم',
              ),
            ),
          ),
        TextField(
          controller: _notesCtrl,
          maxLines: 3,
          decoration: const InputDecoration(
            labelText: 'ملاحظاتك للمصممة',
            hintText:
                'مثال: أبي فتحة الكم أوسع شوي، والطول يلامس الأرض مع كعب ٧ سم...',
            alignLabelWithHint: true,
          ),
        ),
      ],
    );
  }

  Widget _rentPanel() {
    final dateText = _occasionDate == null
        ? 'اختاري تاريخ المناسبة'
        : '${_occasionDate!.year}/${_occasionDate!.month}/${_occasionDate!.day}'
            .toArabicDigits();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        OutlinedButton.icon(
          icon: const Icon(Icons.calendar_today, size: 18),
          label: Text('تاريخ المناسبة: $dateText'),
          onPressed: () async {
            final now = DateTime.now();
            final picked = await showDatePicker(
              context: context,
              initialDate: _occasionDate ?? now.add(const Duration(days: 14)),
              firstDate: now,
              lastDate: now.add(const Duration(days: 365)),
            );
            if (picked != null) setState(() => _occasionDate = picked);
          },
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.okBg,
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Text(
            '🛡️ التأمين الشامل منفصل ويُسترد كاملًا لمحفظتك بعد الإرجاع السليم — التلف الكامل = خصم التأمين بالكامل للمصممة، والتأخير ١٠٪ عن كل يوم.',
            style: TextStyle(fontSize: 12.5, height: 1.5),
          ),
        ),
      ],
    );
  }

  Widget _colorsRow(Product p) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('الألوان غير المتوفرة تظهر معطلة',
            style: TextStyle(fontSize: 12, color: AppColors.mutedInk)),
        const SizedBox(height: 10),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            for (final c in p.colors) _colorSwatch(c),
          ],
        ),
      ],
    );
  }

  Widget _colorSwatch(ProductColor c) {
    final selected = _color == c.name;
    final swatch = _parseHex(c.hex) ?? AppColors.roseSoft;
    return Opacity(
      opacity: c.inStock ? 1 : 0.4,
      child: GestureDetector(
        onTap: c.inStock ? () => setState(() => _color = c.name) : null,
        child: Column(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                color: swatch,
                shape: BoxShape.circle,
                border: Border.all(
                  color: selected ? AppColors.maroon : AppColors.roseSoft,
                  width: selected ? 3 : 1,
                ),
              ),
              child: c.inStock
                  ? null
                  : const Icon(Icons.block, size: 18, color: Colors.black45),
            ),
            const SizedBox(height: 4),
            Text(c.name, style: const TextStyle(fontSize: 11)),
            if (!c.inStock)
              const Text('غير متوفر',
                  style: TextStyle(fontSize: 10, color: AppColors.danger)),
          ],
        ),
      ),
    );
  }

  Widget _addonsSection(Product p) {
    if (p.addons.isEmpty) {
      return const Text(
        'ما حددت المصممة إضافات جاهزة لهذا المنتج — اطلبي اللي تبين بالأسفل.',
        style: TextStyle(fontSize: 13, color: AppColors.mutedInk),
      );
    }
    return Column(
      children: [
        for (final a in p.addons)
          CheckboxListTile(
            contentPadding: EdgeInsets.zero,
            controlAffinity: ListTileControlAffinity.leading,
            value: _addonIds.contains(a.id),
            title: Text('${a.name} +${formatSar(a.price)}'),
            onChanged: (v) => setState(() {
              if (v ?? false) {
                _addonIds.add(a.id);
              } else {
                _addonIds.remove(a.id);
              }
            }),
          ),
      ],
    );
  }

  Widget _contactRow(Product p) {
    final mode = p.commMode ?? 'chat';
    Widget content;
    switch (mode) {
      case 'call':
        content = Row(
          children: [
            const Icon(Icons.phone, color: AppColors.green),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                p.sellerPhone ?? 'دردشة + اتصال',
                textDirection: TextDirection.ltr,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            TextButton.icon(
              icon: const Icon(Icons.call, size: 18),
              label: const Text('اتصال'),
              onPressed: () {},
            ),
          ],
        );
        break;
      case 'payfirst':
        content = const Text(
          '🔒 هذه البائعة تفتح المحادثة بعد إتمام الدفع فقط',
          style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.warnInk),
        );
        break;
      case 'chat':
      default:
        content = const Text('💬 دردشة فقط',
            style: TextStyle(fontWeight: FontWeight.w600));
    }
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.roseBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: content,
    );
  }

  Widget _priceSummary() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        children: [
          _priceRow('السعر الأساسي', _basePrice),
          if (_addonsTotal > 0) _priceRow('الإضافات', _addonsTotal),
          if (_deposit > 0)
            _priceRow('التأمين الشامل المسترد 🛡️', _deposit),
          const Divider(height: 20),
          _priceRow('الإجمالي', _total, bold: true),
        ],
      ),
    );
  }

  Widget _priceRow(String label, num amount, {bool bold = false}) {
    final style = TextStyle(
      fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
      fontSize: bold ? 16 : 14,
      color: bold ? AppColors.maroon : AppColors.ink,
    );
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: style),
          Text(formatSar(amount), style: style),
        ],
      ),
    );
  }

  Color? _parseHex(String? hex) {
    if (hex == null) return null;
    var h = hex.replaceAll('#', '').trim();
    if (h.length == 6) h = 'FF$h';
    final v = int.tryParse(h, radix: 16);
    return v == null ? null : Color(v);
  }
}
