import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';
import '../../models/models.dart';

/// SPEC §3.6 — buyer orders with delivery codes, cancel, return & rating.
class OrdersScreen extends ConsumerStatefulWidget {
  const OrdersScreen({super.key});

  @override
  ConsumerState<OrdersScreen> createState() => _OrdersScreenState();
}

class _Filter {
  const _Filter(this.group, this.label);
  final String group;
  final String label;
}

const _filters = <_Filter>[
  _Filter('all', 'الكل'),
  _Filter('active', 'النشطة'),
  _Filter('done', 'المكتملة'),
  _Filter('cancel', 'الملغاة'),
];

const _statusLabels = <String, String>{
  'paid_escrow': 'مدفوع ومحجوز',
  'accepted': 'قبلته المصممة',
  'in_progress': 'قيد التنفيذ',
  'with_courier': 'مع المندوب',
  'delivered': 'تم التسليم',
  'completed': 'مكتمل',
  'cancelled': 'ملغي',
  'return_requested': 'إرجاع جاري',
  'returned_refunded': 'مُرجَع — استُرد المبلغ',
};

const _returnReasons = <String>[
  'المقاس غير مناسب',
  'المنتج يختلف عن الوصف أو الصور',
  'غيّرت رأيي',
  'عيب في المنتج',
];

const _ratingChips = <String>[
  'جودة القماش ممتازة',
  'مطابق للوصف',
  'تغليف راقي',
  'سرعة بالتجهيز',
  'تعامل رائع',
];

class _OrdersScreenState extends ConsumerState<OrdersScreen> {
  String _group = 'all';
  bool _loading = true;
  Object? _error;
  List<OrderModel> _orders = const [];

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
    try {
      final orders = await ref
          .read(apiRepositoryProvider)
          .orders(group: _group == 'all' ? null : _group);
      if (!mounted) return;
      setState(() {
        _orders = orders;
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

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _cancel(OrderModel order) async {
    try {
      await ref.read(apiRepositoryProvider).cancelOrder(order.id);
      if (!mounted) return;
      _snack('✓ أُلغي الطلب واسترد المبلغ');
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  Future<void> _requestReturn(OrderModel order) async {
    final reason = await showDialog<String>(
      context: context,
      builder: (_) => const _ReturnDialog(),
    );
    if (reason == null) return;
    try {
      await ref.read(apiRepositoryProvider).requestReturn(order.id, reason);
      if (!mounted) return;
      _snack('✓ سُجّل طلب الإرجاع المجاني');
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  Future<void> _rate(OrderModel order) async {
    final result = await showDialog<_RatingResult>(
      context: context,
      builder: (_) => const _RatingDialog(),
    );
    if (result == null) return;
    try {
      await ref.read(apiRepositoryProvider).rateOrder(
          order.id, result.stars, result.chips, result.text);
      if (!mounted) return;
      _snack('✓ نُشر تقييمك (${result.stars.toString().toArabicDigits()} ★)');
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('طلباتي')),
      body: Column(
        children: [
          _filterTabs(),
          Expanded(child: _buildBody()),
        ],
      ),
    );
  }

  Widget _filterTabs() {
    return SizedBox(
      height: 52,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        itemCount: _filters.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (_, i) {
          final f = _filters[i];
          final selected = f.group == _group;
          return ChoiceChip(
            label: Text(f.label),
            selected: selected,
            onSelected: (_) {
              setState(() => _group = f.group);
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
    if (_orders.isEmpty) {
      return const Center(
        child: Text('ما عندك طلبات هنا 📦',
            style: TextStyle(color: AppColors.mutedInk, fontSize: 16)),
      );
    }
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _orders.length,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (_, i) => _orderCard(_orders[i]),
      ),
    );
  }

  Widget _orderCard(OrderModel order) {
    final completed = order.status == 'completed';
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text('#${order.code}',
                  style: const TextStyle(
                      fontWeight: FontWeight.w700, fontSize: 15)),
              const Spacer(),
              _StatusChip(status: order.status),
            ],
          ),
          if (order.storeName != null) ...[
            const SizedBox(height: 4),
            Text(order.storeName!,
                style: const TextStyle(
                    fontSize: 13, color: AppColors.mutedInk)),
          ],
          const SizedBox(height: 8),
          Text(formatSar(order.total),
              style: const TextStyle(
                  fontWeight: FontWeight.w700, color: AppColors.maroon)),
          if (order.deliveryCode != null) ...[
            const SizedBox(height: 12),
            _deliveryCodeBox(order.deliveryCode!),
          ],
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (order.cancellable)
                OutlinedButton(
                  onPressed: () => _cancel(order),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.danger,
                    side: const BorderSide(color: AppColors.danger),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(99)),
                  ),
                  child: const Text('إلغاء مجاني'),
                ),
              if (completed) ...[
                OutlinedButton(
                  onPressed: () => _requestReturn(order),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.maroon,
                    side: const BorderSide(color: AppColors.roseSoft),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(99)),
                  ),
                  child: const Text('↩️ طلب إرجاع مجاني'),
                ),
                ElevatedButton(
                  onPressed: () => _rate(order),
                  child: const Text('⭐ قيّمي التجربة'),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }

  Widget _deliveryCodeBox(String code) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.warnBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.goldLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'كود التسليم — لا تعطينه للمندوب أو البائعة إلا بعد استلام القطعة ومعاينته',
            style: TextStyle(
                fontSize: 12, height: 1.5, color: AppColors.warnInk),
          ),
          const SizedBox(height: 10),
          Center(
            child: Text(
              code.toArabicDigits(),
              style: const TextStyle(
                fontSize: 34,
                fontWeight: FontWeight.w800,
                letterSpacing: 8,
                color: AppColors.warnInk,
              ),
            ),
          ),
          const SizedBox(height: 10),
          const Text(
            '⚠ إعطاء الكود = تأكيد الاستلام وتحويل المبلغ للبائعة',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: AppColors.danger),
          ),
        ],
      ),
    );
  }
}

/// Shared status chip mapping order.status → Arabic label with tone.
class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.status});
  final String status;

  @override
  Widget build(BuildContext context) {
    final label = _statusLabels[status] ?? status;
    Color bg;
    Color fg;
    switch (status) {
      case 'completed':
      case 'delivered':
        bg = AppColors.okBg;
        fg = AppColors.green;
        break;
      case 'cancelled':
        bg = AppColors.roseBg;
        fg = AppColors.mutedInk;
        break;
      case 'return_requested':
      case 'returned_refunded':
        bg = AppColors.warnBg;
        fg = AppColors.warnInk;
        break;
      default:
        bg = AppColors.roseBg;
        fg = AppColors.maroon;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(99),
      ),
      child: Text(label,
          style: TextStyle(
              color: fg, fontSize: 12, fontWeight: FontWeight.w700)),
    );
  }
}

class _ReturnDialog extends StatefulWidget {
  const _ReturnDialog();

  @override
  State<_ReturnDialog> createState() => _ReturnDialogState();
}

class _ReturnDialogState extends State<_ReturnDialog> {
  String _reason = _returnReasons.first;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('↩️ طلب إرجاع مجاني'),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('وش سبب الإرجاع؟',
              style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          RadioGroup<String>(
            groupValue: _reason,
            onChanged: (v) => setState(() => _reason = v!),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                for (final r in _returnReasons)
                  RadioListTile<String>(
                    value: r,
                    title: Text(r, style: const TextStyle(fontSize: 14)),
                    contentPadding: EdgeInsets.zero,
                    dense: true,
                  ),
              ],
            ),
          ),
          const SizedBox(height: 4),
          const Text('الإرجاع خلال ٧ أيام مجاني تمامًا',
              style: TextStyle(fontSize: 12, color: AppColors.green)),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('إلغاء'),
        ),
        ElevatedButton(
          onPressed: () => Navigator.pop(context, _reason),
          child: const Text('تأكيد طلب الإرجاع'),
        ),
      ],
    );
  }
}

class _RatingResult {
  const _RatingResult(this.stars, this.chips, this.text);
  final int stars;
  final List<String> chips;
  final String? text;
}

class _RatingDialog extends StatefulWidget {
  const _RatingDialog();

  @override
  State<_RatingDialog> createState() => _RatingDialogState();
}

class _RatingDialogState extends State<_RatingDialog> {
  int _stars = 5;
  final Set<String> _chips = {};
  final _textCtrl = TextEditingController();

  @override
  void dispose() {
    _textCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('⭐ قيّمي تجربتك'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                for (var i = 1; i <= 5; i++)
                  IconButton(
                    onPressed: () => setState(() => _stars = i),
                    icon: Icon(
                      i <= _stars ? Icons.star : Icons.star_border,
                      color: AppColors.gold,
                      size: 32,
                    ),
                    padding: EdgeInsets.zero,
                    constraints:
                        const BoxConstraints(minWidth: 40, minHeight: 40),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final c in _ratingChips)
                  FilterChip(
                    label: Text(c, style: const TextStyle(fontSize: 12)),
                    selected: _chips.contains(c),
                    onSelected: (sel) => setState(() {
                      if (sel) {
                        _chips.add(c);
                      } else {
                        _chips.remove(c);
                      }
                    }),
                    selectedColor: AppColors.roseSoft,
                    backgroundColor: AppColors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(99),
                      side: const BorderSide(color: AppColors.roseSoft),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _textCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                hintText: 'اكتبي رأيك (اختياري)',
              ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('إلغاء'),
        ),
        ElevatedButton(
          onPressed: () {
            final text = _textCtrl.text.trim();
            Navigator.pop(
              context,
              _RatingResult(
                _stars,
                _chips.toList(),
                text.isEmpty ? null : text,
              ),
            );
          },
          child: const Text('نشر التقييم'),
        ),
      ],
    );
  }
}
