import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';

/// SPEC §3.8 — seller dashboard: stats, delivery codes, store status,
/// contact policy and store coupons.
class SellerDashboardScreen extends ConsumerStatefulWidget {
  const SellerDashboardScreen({super.key});

  @override
  ConsumerState<SellerDashboardScreen> createState() =>
      _SellerDashboardScreenState();
}

class _Option {
  const _Option(this.value, this.label);
  final String value;
  final String label;
}

const _storeStatuses = <_Option>[
  _Option('open', 'مفتوح'),
  _Option('paused', 'إيقاف الطلبات'),
  _Option('closed', 'مقفل'),
];

const _commModes = <_Option>[
  _Option('chat', 'دردشة فقط'),
  _Option('call', 'دردشة واتصال'),
  _Option('payfirst', 'الدفع أولًا'),
];

const _couponKinds = <_Option>[
  _Option('fix', 'مبلغ ثابت'),
  _Option('pct', 'نسبة'),
];

class _SellerDashboardScreenState
    extends ConsumerState<SellerDashboardScreen> {
  // Delivery confirmation form.
  final _orderIdCtrl = TextEditingController();
  final _codeCtrl = TextEditingController();

  // New coupon form.
  final _couponCodeCtrl = TextEditingController();
  final _couponValueCtrl = TextEditingController();
  final _couponMinCtrl = TextEditingController();
  String _couponKind = 'fix';

  String _storeStatus = 'open';
  String _commMode = 'chat';

  bool _loading = true;
  Object? _error;
  Map<String, dynamic> _dashboard = const {};
  List<Map<String, dynamic>> _coupons = const [];

  bool _confirming = false;
  bool _creatingCoupon = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _orderIdCtrl.dispose();
    _codeCtrl.dispose();
    _couponCodeCtrl.dispose();
    _couponValueCtrl.dispose();
    _couponMinCtrl.dispose();
    super.dispose();
  }

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final api = ref.read(apiRepositoryProvider);
    try {
      final results = await Future.wait([
        api.sellerDashboard(),
        api.sellerCoupons(),
      ]);
      if (!mounted) return;
      final dash = results[0] as Map<String, dynamic>;
      setState(() {
        _dashboard = dash;
        _coupons = (results[1] as List).cast<Map<String, dynamic>>();
        final status = dash['store_status'] ?? dash['status'];
        if (status is String) _storeStatus = status;
        final comm = dash['comm_mode'];
        if (comm is String) _commMode = comm;
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

  num _numOf(String key, [num fallback = 0]) {
    final v = _dashboard[key];
    if (v is num) return v;
    if (v is String) return num.tryParse(v) ?? fallback;
    return fallback;
  }

  // --- Delivery confirmation ---
  Future<void> _confirmDelivery() async {
    final orderId = int.tryParse(_orderIdCtrl.text.trim());
    final code = _codeCtrl.text.trim();
    if (orderId == null) {
      _snack('أدخلي رقم الطلب');
      return;
    }
    if (code.length != 4) {
      _snack('كود التسليم أربعة أرقام');
      return;
    }
    setState(() => _confirming = true);
    try {
      await ref.read(apiRepositoryProvider).confirmDelivery(orderId, code);
      if (!mounted) return;
      _snack('✓ تم التسليم — المبلغ تحوّل لمحفظتك');
      _orderIdCtrl.clear();
      _codeCtrl.clear();
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _confirming = false);
    }
  }

  // --- Store status / contact policy ---
  Future<void> _updateStoreStatus(String value) async {
    final prev = _storeStatus;
    setState(() => _storeStatus = value);
    try {
      await ref.read(apiRepositoryProvider).updateStore({'status': value});
      _snack('✓ حُدّثت حالة المتجر');
    } on ApiException catch (e) {
      if (mounted) setState(() => _storeStatus = prev);
      _snack(e.message);
    } catch (_) {
      if (mounted) setState(() => _storeStatus = prev);
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  Future<void> _updateCommMode(String value) async {
    final prev = _commMode;
    setState(() => _commMode = value);
    try {
      await ref.read(apiRepositoryProvider).updateStore({'comm_mode': value});
      _snack('✓ حُدّثت سياسة التواصل');
    } on ApiException catch (e) {
      if (mounted) setState(() => _commMode = prev);
      _snack(e.message);
    } catch (_) {
      if (mounted) setState(() => _commMode = prev);
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  // --- Coupons ---
  Future<void> _toggleCoupon(int id, bool active) async {
    try {
      await ref
          .read(apiRepositoryProvider)
          .updateSellerCoupon(id, {'active': !active});
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  Future<void> _deleteCoupon(int id) async {
    try {
      await ref.read(apiRepositoryProvider).deleteSellerCoupon(id);
      _snack('✓ حُذف الكوبون');
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  Future<void> _createCoupon() async {
    final code = _couponCodeCtrl.text.trim().toUpperCase();
    final value = num.tryParse(_couponValueCtrl.text.trim());
    final min = num.tryParse(_couponMinCtrl.text.trim());
    if (code.isEmpty) {
      _snack('أدخلي كود الكوبون');
      return;
    }
    if (value == null) {
      _snack('أدخلي قيمة الخصم');
      return;
    }
    setState(() => _creatingCoupon = true);
    try {
      await ref.read(apiRepositoryProvider).createSellerCoupon({
        'code': code,
        'kind': _couponKind,
        'value': value,
        if (min != null) 'min': min,
      });
      if (!mounted) return;
      _snack('✓ أُضيف الكوبون');
      _couponCodeCtrl.clear();
      _couponValueCtrl.clear();
      _couponMinCtrl.clear();
      await _load();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _creatingCoupon = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('لوحة تحكم المصممة'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'تحديث',
            onPressed: _load,
          ),
        ],
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
        padding: const EdgeInsets.all(16),
        children: [
          _statCards(),
          const SizedBox(height: 20),
          _deliverySection(),
          const SizedBox(height: 20),
          _storeStatusSection(),
          const SizedBox(height: 20),
          _commModeSection(),
          const SizedBox(height: 20),
          _couponsSection(),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => context.push('/listing'),
              child: const Text('＋ أضيفي منتج جديد'),
            ),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _statCards() {
    final rating = _numOf('rating');
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 12,
      crossAxisSpacing: 12,
      childAspectRatio: 1.7,
      children: [
        _statCard('رصيد قابل للسحب', formatSar(_numOf('withdrawable'))),
        _statCard('مبيعات هذا الشهر', formatSar(_numOf('sales_this_month'))),
        _statCard(
            'صافي الدخل بعد عمولة ١٢٪', formatSar(_numOf('net_after_commission'))),
        _statCard('تقييم متجرك',
            rating == 0 ? '—' : '★ ${rating.toString().toArabicDigits()}'),
      ],
    );
  }

  Widget _statCard(String label, String value) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(label,
              style: const TextStyle(
                  fontSize: 12.5, color: AppColors.mutedInk)),
          const SizedBox(height: 6),
          Text(value,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 17,
                  color: AppColors.maroon)),
        ],
      ),
    );
  }

  Widget _card({required String title, required List<Widget> children}) {
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
          Text(title,
              style: const TextStyle(
                  fontWeight: FontWeight.w700, fontSize: 15.5)),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }

  Widget _deliverySection() {
    final orders = (_dashboard['orders'] as List?)?.cast<dynamic>() ?? const [];
    return _card(
      title: '🧾 الطلبات وكود التسليم',
      children: [
        if (orders.isNotEmpty) ...[
          for (final o in orders) ...[
            _orderLine(o as Map<String, dynamic>),
            const SizedBox(height: 8),
          ],
          const Divider(height: 24),
        ],
        const Text('أدخلي رقم الطلب وكود التسليم لتحرير المبلغ',
            style: TextStyle(fontSize: 12.5, color: AppColors.mutedInk)),
        const SizedBox(height: 10),
        TextField(
          controller: _orderIdCtrl,
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(labelText: 'رقم الطلب'),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _codeCtrl,
          keyboardType: TextInputType.number,
          maxLength: 4,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          decoration: const InputDecoration(
            labelText: 'كود التسليم (٤ أرقام)',
            counterText: '',
          ),
        ),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _confirming ? null : _confirmDelivery,
            child: _confirming
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white),
                  )
                : const Text('تأكيد التسليم'),
          ),
        ),
      ],
    );
  }

  Widget _orderLine(Map<String, dynamic> o) {
    final code = (o['code'] ?? o['id'] ?? '').toString();
    final title = (o['title'] ?? o['product']?['title'] ?? '').toString();
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.roseBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('#${code.toArabicDigits()}',
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                if (title.isNotEmpty) ...[
                  const SizedBox(height: 2),
                  Text(title,
                      style: const TextStyle(
                          fontSize: 12.5, color: AppColors.mutedInk)),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _storeStatusSection() {
    final preview = _storeStatuses
        .firstWhere((s) => s.value == _storeStatus,
            orElse: () => _storeStatuses.first)
        .label;
    return _card(
      title: '🏪 حالة المتجر',
      children: [
        RadioGroup<String>(
          groupValue: _storeStatus,
          onChanged: (v) => v == null ? null : _updateStoreStatus(v),
          child: Column(
            children: [
              for (final s in _storeStatuses)
                RadioListTile<String>(
                  value: s.value,
                  title: Text(s.label),
                  contentPadding: EdgeInsets.zero,
                  activeColor: AppColors.maroon,
                ),
            ],
          ),
        ),
        const SizedBox(height: 4),
        Text('المعاينة: متجرك الآن «$preview»',
            style: const TextStyle(fontSize: 12.5, color: AppColors.green)),
      ],
    );
  }

  Widget _commModeSection() {
    final preview = _commModes
        .firstWhere((s) => s.value == _commMode,
            orElse: () => _commModes.first)
        .label;
    return _card(
      title: '📞 سياسة التواصل',
      children: [
        RadioGroup<String>(
          groupValue: _commMode,
          onChanged: (v) => v == null ? null : _updateCommMode(v),
          child: Column(
            children: [
              for (final m in _commModes)
                RadioListTile<String>(
                  value: m.value,
                  title: Text(m.label),
                  contentPadding: EdgeInsets.zero,
                  activeColor: AppColors.maroon,
                ),
            ],
          ),
        ),
        const SizedBox(height: 4),
        Text('المعاينة: «$preview»',
            style: const TextStyle(fontSize: 12.5, color: AppColors.green)),
      ],
    );
  }

  Widget _couponsSection() {
    return _card(
      title: '🎟️ كوبونات متجري',
      children: [
        if (_coupons.isEmpty)
          const Text('ما عندك كوبونات بعد',
              style: TextStyle(color: AppColors.mutedInk))
        else
          for (final c in _coupons) ...[
            _couponRow(c),
            const SizedBox(height: 8),
          ],
        const Divider(height: 24),
        const Text('كوبون جديد',
            style: TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 10),
        TextField(
          controller: _couponCodeCtrl,
          textCapitalization: TextCapitalization.characters,
          inputFormatters: [
            FilteringTextInputFormatter.allow(RegExp(r'[A-Za-z0-9]')),
            _UpperCaseFormatter(),
          ],
          decoration: const InputDecoration(labelText: 'الكود'),
        ),
        const SizedBox(height: 10),
        DropdownButtonFormField<String>(
          initialValue: _couponKind,
          isExpanded: true,
          decoration: const InputDecoration(labelText: 'نوع الخصم'),
          items: [
            for (final k in _couponKinds)
              DropdownMenuItem(value: k.value, child: Text(k.label)),
          ],
          onChanged: (v) => v == null ? null : setState(() => _couponKind = v),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _couponValueCtrl,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: InputDecoration(
            labelText: _couponKind == 'pct' ? 'النسبة (٪)' : 'قيمة الخصم',
          ),
        ),
        const SizedBox(height: 10),
        TextField(
          controller: _couponMinCtrl,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(labelText: 'حد أدنى (اختياري)'),
        ),
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: _creatingCoupon ? null : _createCoupon,
            child: _creatingCoupon
                ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: Colors.white),
                  )
                : const Text('إضافة الكوبون'),
          ),
        ),
      ],
    );
  }

  Widget _couponRow(Map<String, dynamic> c) {
    final id = c['id'] is int ? c['id'] as int : int.tryParse('${c['id']}');
    final code = (c['code'] ?? '').toString();
    final kind = (c['kind'] ?? c['type'] ?? 'fix').toString();
    final value = (c['value'] ?? 0) as num;
    final min = c['min'];
    final active = (c['active'] as bool?) ?? (c['is_active'] as bool?) ?? false;
    final discount = kind == 'pct'
        ? 'خصم ${value.toString().toArabicDigits()}٪'
        : 'خصم ${formatSar(value)}';
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.roseBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(code,
                    style: const TextStyle(
                        fontWeight: FontWeight.w700, fontSize: 14.5)),
              ),
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: active ? AppColors.okBg : AppColors.warnBg,
                  borderRadius: BorderRadius.circular(99),
                ),
                child: Text(active ? 'فعّال' : 'موقّف',
                    style: TextStyle(
                        fontSize: 11,
                        color:
                            active ? AppColors.green : AppColors.warnInk)),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(discount,
              style: const TextStyle(fontSize: 12.5, color: AppColors.ink)),
          if (min is num)
            Text('حد أدنى ${formatSar(min)}',
                style: const TextStyle(
                    fontSize: 12, color: AppColors.mutedInk)),
          const SizedBox(height: 6),
          Row(
            children: [
              TextButton(
                onPressed:
                    id == null ? null : () => _toggleCoupon(id, active),
                child: Text(active ? 'إيقاف' : 'تفعيل'),
              ),
              TextButton(
                onPressed: id == null ? null : () => _deleteCoupon(id),
                style: TextButton.styleFrom(
                    foregroundColor: AppColors.danger),
                child: const Text('حذف'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _UpperCaseFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
      TextEditingValue oldValue, TextEditingValue newValue) {
    return newValue.copyWith(text: newValue.text.toUpperCase());
  }
}
