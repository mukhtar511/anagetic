import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';

/// SPEC §3.5 — cart & checkout (escrow, coupons, payment methods).
class CartScreen extends ConsumerStatefulWidget {
  const CartScreen({super.key});

  @override
  ConsumerState<CartScreen> createState() => _CartScreenState();
}

class _PaymentMethod {
  const _PaymentMethod(this.value, this.label);
  final String value;
  final String label;
}

const _paymentMethods = <_PaymentMethod>[
  _PaymentMethod('mada', 'مدى'),
  _PaymentMethod('card', 'بطاقة ائتمانية'),
  _PaymentMethod('applepay', 'Apple Pay'),
  _PaymentMethod('tabby', 'تابي — ٤ دفعات'),
  _PaymentMethod('tamara', 'تمارا'),
  _PaymentMethod('wallet', 'محفظة أناقتك'),
];

class _CartLine {
  _CartLine({
    required this.id,
    required this.title,
    required this.store,
    required this.meta,
    required this.price,
  });
  final int? id;
  final String title;
  final String store;
  final String meta;
  final num price;

  factory _CartLine.fromJson(Map<String, dynamic> j) {
    final store = (j['store_name'] ?? j['store']?['name'] ?? j['store'] ?? '')
        .toString();
    // Build the meta line (mode · color · addons) defensively.
    final parts = <String>[];
    final mode = (j['mode_label'] ?? j['mode'])?.toString();
    if (mode != null && mode.isNotEmpty) parts.add(mode);
    final color = (j['color'] ?? j['color_name'])?.toString();
    if (color != null && color.isNotEmpty) parts.add('اللون: $color');
    final addons = j['addons'];
    if (addons is List && addons.isNotEmpty) {
      final names = addons
          .map((a) => a is Map ? (a['name'] ?? '').toString() : a.toString())
          .where((s) => s.isNotEmpty)
          .join(' · ');
      if (names.isNotEmpty) parts.add('+ إضافة: $names');
    }
    final rawMeta = (j['meta'] ?? j['subtitle'])?.toString();
    final meta = rawMeta != null && rawMeta.isNotEmpty ? rawMeta : parts.join(' · ');
    return _CartLine(
      id: j['id'] is int ? j['id'] as int : int.tryParse('${j['id']}'),
      title: (j['title'] ?? j['name'] ?? '').toString(),
      store: store,
      meta: meta,
      price: (j['price'] ?? j['total'] ?? 0) as num,
    );
  }
}

class _CartScreenState extends ConsumerState<CartScreen> {
  final _couponCtrl = TextEditingController();

  bool _loading = true;
  bool _paying = false;
  Object? _error;
  List<_CartLine> _lines = const [];
  num _subtotal = 0;
  num _delivery = 0;
  num _discount = 0;
  num _total = 0;
  String? _coupon;
  String _method = 'mada';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _couponCtrl.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final data = await ref.read(apiRepositoryProvider).cart();
      if (!mounted) return;
      final rawItems = (data['items'] ?? data['lines'] ?? const []) as List;
      final lines = rawItems
          .map((e) => _CartLine.fromJson(e as Map<String, dynamic>))
          .toList();
      final totals = (data['totals'] ?? data) as Map<String, dynamic>;
      num pick(List<String> keys) {
        for (final k in keys) {
          final v = totals[k] ?? data[k];
          if (v is num) return v;
        }
        return 0;
      }

      final subtotal = pick(['subtotal', 'sub_total']);
      final delivery = pick(['delivery', 'shipping', 'delivery_total']);
      final discount = pick(['discount', 'coupon_discount']);
      var total = pick(['total', 'grand_total']);
      final computed =
          lines.fold<num>(0, (a, l) => a + l.price) + delivery - discount;
      setState(() {
        _lines = lines;
        _subtotal = subtotal != 0
            ? subtotal
            : lines.fold<num>(0, (a, l) => a + l.price);
        _delivery = delivery;
        _discount = discount;
        _total = total != 0 ? total : computed;
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

  void _applyCoupon() {
    final code = _couponCtrl.text.trim().toUpperCase();
    if (code.isEmpty) return;
    setState(() => _coupon = code);
    _snack('🎟️ سيُطبَّق الكوبون «$code» عند الدفع');
  }

  void _removeLine(_CartLine line) {
    setState(() {
      _lines = _lines.where((l) => l != line).toList();
      _subtotal = _lines.fold<num>(0, (a, l) => a + l.price);
      _total = _subtotal + _delivery - _discount;
    });
    _snack('حُذف العنصر من السلة');
  }

  Future<void> _checkout() async {
    if (_lines.isEmpty || _paying) return;
    setState(() => _paying = true);
    try {
      await ref
          .read(apiRepositoryProvider)
          .checkout(_method, coupon: _coupon);
      if (!mounted) return;
      _snack('✓ تم الدفع وحجز المبلغ!');
      context.go('/orders');
    } on ApiException catch (e) {
      if (!mounted) return;
      _snack(e.message);
    } catch (e) {
      if (!mounted) return;
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _paying = false);
    }
  }

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('سلتي')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null) {
      return _ErrorView(error: _error!, onRetry: _load);
    }
    if (_lines.isEmpty) {
      return _emptyState();
    }
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        for (final line in _lines) ...[
          _lineCard(line),
          const SizedBox(height: 12),
        ],
        _escrowNote(),
        const SizedBox(height: 16),
        _couponField(),
        const SizedBox(height: 16),
        _paymentSelector(),
        const SizedBox(height: 16),
        _summary(),
        const SizedBox(height: 16),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _paying ? null : _checkout,
            child: _paying
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white),
                  )
                : Text('ادفعي ${formatSar(_total)}'),
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }

  Widget _emptyState() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Text('سلتك فاضية 🛍️',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700)),
          const SizedBox(height: 12),
          TextButton(
            onPressed: () => context.go('/'),
            child: const Text('تصفحي تشكيلات المصممات ←'),
          ),
        ],
      ),
    );
  }

  Widget _lineCard(_CartLine line) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(line.title,
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 15)),
                const SizedBox(height: 4),
                Text(line.store,
                    style: const TextStyle(
                        fontSize: 12.5, color: AppColors.mutedInk)),
                if (line.meta.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(line.meta,
                      style: const TextStyle(fontSize: 12.5, height: 1.4)),
                ],
                const SizedBox(height: 8),
                Text(formatSar(line.price),
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, color: AppColors.maroon)),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.delete_outline, color: AppColors.danger),
            tooltip: 'حذف',
            onPressed: () => _removeLine(line),
          ),
        ],
      ),
    );
  }

  Widget _escrowNote() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.okBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.green.withValues(alpha: 0.3)),
      ),
      child: const Text(
        '🔒 مبلغك محجوز لدى أناقتك ولا يصل البائعات إلا بعد تأكيدك الاستلام بكود التسليم · الاسترجاع خلال ٧ أيام مجاني',
        style: TextStyle(fontSize: 12.5, height: 1.5, color: AppColors.green),
      ),
    );
  }

  Widget _couponField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('🎟️ عندك كوبون؟',
            style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _couponCtrl,
                textCapitalization: TextCapitalization.characters,
                decoration: const InputDecoration(
                  hintText: 'اكتبي الكود — جربي ANAQA10 أو REEM100',
                ),
              ),
            ),
            const SizedBox(width: 8),
            ElevatedButton(
              onPressed: _applyCoupon,
              child: const Text('تطبيق'),
            ),
          ],
        ),
        if (_coupon != null) ...[
          const SizedBox(height: 6),
          Text('الكوبون الحالي: $_coupon',
              style: const TextStyle(fontSize: 12, color: AppColors.green)),
        ],
      ],
    );
  }

  Widget _paymentSelector() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('طريقة الدفع',
            style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (final m in _paymentMethods)
              ChoiceChip(
                label: Text(m.label),
                selected: _method == m.value,
                onSelected: (_) => setState(() => _method = m.value),
                selectedColor: AppColors.maroon,
                labelStyle: TextStyle(
                  color: _method == m.value ? Colors.white : AppColors.ink,
                  fontWeight: FontWeight.w600,
                ),
                backgroundColor: AppColors.white,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(99),
                  side: const BorderSide(color: AppColors.roseSoft),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _summary() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.roseBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        children: [
          _summaryRow('المجموع الفرعي', formatSar(_subtotal)),
          if (_delivery > 0) ...[
            const SizedBox(height: 6),
            _summaryRow('التوصيل', formatSar(_delivery)),
          ],
          if (_discount > 0) ...[
            const SizedBox(height: 6),
            _summaryRow('🎟️ خصم الكوبون', '− ${formatSar(_discount)}',
                color: AppColors.green),
          ],
          const Divider(height: 20),
          _summaryRow('الإجمالي', formatSar(_total), bold: true),
        ],
      ),
    );
  }

  Widget _summaryRow(String label, String value,
      {bool bold = false, Color? color}) {
    final style = TextStyle(
      fontWeight: bold ? FontWeight.w700 : FontWeight.w500,
      fontSize: bold ? 16 : 14,
      color: color ?? AppColors.ink,
    );
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [Text(label, style: style), Text(value, style: style)],
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({required this.error, required this.onRetry});
  final Object error;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, color: AppColors.danger, size: 40),
            const SizedBox(height: 12),
            Text(error.toString(),
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.danger)),
            const SizedBox(height: 12),
            ElevatedButton(
                onPressed: onRetry, child: const Text('إعادة المحاولة')),
          ],
        ),
      ),
    );
  }
}
