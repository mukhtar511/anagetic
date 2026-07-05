import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';

/// SPEC §3.9 — smart request: buyer posts a need, sellers compete with offers.
class SmartRequestScreen extends ConsumerStatefulWidget {
  const SmartRequestScreen({super.key});

  @override
  ConsumerState<SmartRequestScreen> createState() =>
      _SmartRequestScreenState();
}

class _Option {
  const _Option(this.value, this.label);
  final String value;
  final String label;
}

const _categories = <_Option>[
  _Option('dress', 'فساتين'),
  _Option('abaya', 'عبايات'),
  _Option('care', 'أدوات عناية'),
  _Option('acc', 'إكسسوارات'),
  _Option('nobrand', 'بدون براند'),
  _Option('used', 'المستعمل'),
];

const _sizes = <_Option>[
  _Option('S', 'S'),
  _Option('M', 'M'),
  _Option('L', 'L'),
  _Option('XL', 'XL'),
  _Option('XXL', 'XXL'),
  _Option('saved', 'حسب مقاساتي المحفوظة'),
];

const _types = <_Option>[
  _Option('buy', 'شراء'),
  _Option('rent', 'تأجير'),
  _Option('both', 'شراء أو تأجير'),
  _Option('custom', '🎨 تصميم خاص — عندي صورة موديل'),
];

const _scopes = <_Option>[
  _Option('region_first', 'منطقتي أولًا ثم الجميع'),
  _Option('region_only', 'منطقتي فقط'),
  _Option('all', 'كل المناطق'),
];

class _SmartRequestScreenState extends ConsumerState<SmartRequestScreen> {
  final _descCtrl = TextEditingController();
  final _budgetCtrl = TextEditingController();

  String _category = 'dress';
  String _size = 'M';
  String _type = 'buy';
  String _scope = 'region_first';
  DateTime? _needBy;

  bool _submitting = false;
  bool _loadingList = true;
  Object? _listError;
  List<Map<String, dynamic>> _requests = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadRequests());
  }

  @override
  void dispose() {
    _descCtrl.dispose();
    _budgetCtrl.dispose();
    super.dispose();
  }

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _loadRequests() async {
    setState(() {
      _loadingList = true;
      _listError = null;
    });
    try {
      final list = await ref.read(apiRepositoryProvider).smartRequests();
      if (!mounted) return;
      setState(() {
        _requests = list;
        _loadingList = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _listError = e;
        _loadingList = false;
      });
    }
  }

  Future<void> _submit() async {
    final desc = _descCtrl.text.trim();
    if (desc.isEmpty) {
      _snack('اكتبي وصف طلبك أولًا');
      return;
    }
    final budget = num.tryParse(_budgetCtrl.text.trim());
    if (budget == null) {
      _snack('أدخلي ميزانية قصوى صحيحة');
      return;
    }
    setState(() => _submitting = true);
    try {
      await ref.read(apiRepositoryProvider).createSmartRequest({
        'description': desc,
        'category': _category,
        'size': _size,
        'budget': budget,
        if (_needBy != null)
          'need_by':
              '${_needBy!.year.toString().padLeft(4, '0')}-${_needBy!.month.toString().padLeft(2, '0')}-${_needBy!.day.toString().padLeft(2, '0')}',
        'type': _type,
        'scope': _scope,
      });
      if (!mounted) return;
      _snack('🪄 نُشر طلبك للبائعات');
      _descCtrl.clear();
      _budgetCtrl.clear();
      setState(() => _needBy = null);
      await _loadRequests();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  Future<void> _acceptOffer(int offerId) async {
    try {
      await ref.read(apiRepositoryProvider).acceptOffer(offerId);
      if (!mounted) return;
      _snack('✓ قبلتِ العرض — سينشأ الطلب ويُحجز المبلغ لدى أناقتك');
      await _loadRequests();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    }
  }

  Future<void> _pickNeedBy() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _needBy ?? now,
      firstDate: now,
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => _needBy = picked);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('🪄 الطلب الذكي')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text('🪄 الطلب الذكي — البائعات يتنافسن عليك',
              style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 16),
          _form(),
          const SizedBox(height: 8),
          const Text(
            'مجاني تمامًا — وما يوصل رقمك لأي بائعة، التواصل كله عبر المنصة',
            style: TextStyle(fontSize: 12.5, color: AppColors.green),
          ),
          const SizedBox(height: 24),
          const Text('طلباتي وعروضها',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
          const SizedBox(height: 12),
          _requestsSection(),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _form() {
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
          const Text('وش تحتاجين؟',
              style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          TextField(
            controller: _descCtrl,
            maxLines: 3,
            decoration: const InputDecoration(
              hintText:
                  'مثال: أبي فستان سهرة راقي لحفل زواج يوم الجمعة',
            ),
          ),
          const SizedBox(height: 14),
          _dropdown('القسم', _category, _categories,
              (v) => setState(() => _category = v)),
          const SizedBox(height: 14),
          _dropdown('المقاس', _size, _sizes, (v) => setState(() => _size = v)),
          const SizedBox(height: 14),
          const Text('الميزانية القصوى',
              style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          TextField(
            controller: _budgetCtrl,
            keyboardType:
                const TextInputType.numberWithOptions(decimal: true),
            decoration: const InputDecoration(hintText: 'ر.س'),
          ),
          const SizedBox(height: 14),
          const Text('أحتاجه قبل تاريخ',
              style: TextStyle(fontWeight: FontWeight.w700)),
          const SizedBox(height: 8),
          OutlinedButton.icon(
            onPressed: _pickNeedBy,
            icon: const Icon(Icons.calendar_today, size: 18),
            label: Text(
              _needBy == null
                  ? 'اختاري تاريخًا'
                  : '${_needBy!.year}-${_needBy!.month.toString().padLeft(2, '0')}-${_needBy!.day.toString().padLeft(2, '0')}'
                      .toArabicDigits(),
            ),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.maroon,
              side: const BorderSide(color: AppColors.roseSoft),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 14),
          _dropdown('نوع الطلب', _type, _types, (v) => setState(() => _type = v)),
          const SizedBox(height: 14),
          _dropdown('نطاق البائعات', _scope, _scopes,
              (v) => setState(() => _scope = v)),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _submitting ? null : _submit,
              child: _submitting
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('🪄 انشري طلبك للبائعات'),
            ),
          ),
        ],
      ),
    );
  }

  Widget _dropdown(String label, String value, List<_Option> options,
      ValueChanged<String> onChanged) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.w700)),
        const SizedBox(height: 8),
        DropdownButtonFormField<String>(
          initialValue: value,
          isExpanded: true,
          items: [
            for (final o in options)
              DropdownMenuItem(value: o.value, child: Text(o.label)),
          ],
          onChanged: (v) {
            if (v != null) onChanged(v);
          },
        ),
      ],
    );
  }

  Widget _requestsSection() {
    if (_loadingList) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 24),
        child: Center(child: CircularProgressIndicator()),
      );
    }
    if (_listError != null) {
      return Column(
        children: [
          Text(_listError.toString(),
              style: const TextStyle(color: AppColors.danger)),
          const SizedBox(height: 8),
          ElevatedButton(
              onPressed: _loadRequests, child: const Text('إعادة المحاولة')),
        ],
      );
    }
    if (_requests.isEmpty) {
      return const Text('ما عندك طلبات ذكية بعد',
          style: TextStyle(color: AppColors.mutedInk));
    }
    return Column(
      children: [
        for (final r in _requests) ...[
          _requestCard(r),
          const SizedBox(height: 12),
        ],
      ],
    );
  }

  Widget _requestCard(Map<String, dynamic> r) {
    final desc = (r['description'] ?? r['title'] ?? '').toString();
    final offers = (r['offers'] as List?) ?? const [];
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
          Text(desc,
              style: const TextStyle(
                  fontWeight: FontWeight.w700, fontSize: 14.5)),
          const SizedBox(height: 4),
          Text(
            offers.isEmpty
                ? 'بانتظار عروض البائعات...'
                : '${offers.length.toString().toArabicDigits()} عرض',
            style: const TextStyle(fontSize: 12.5, color: AppColors.mutedInk),
          ),
          for (final o in offers) ...[
            const SizedBox(height: 10),
            _offerRow(o as Map<String, dynamic>),
          ],
        ],
      ),
    );
  }

  Widget _offerRow(Map<String, dynamic> o) {
    final store =
        (o['store_name'] ?? o['store']?['name'] ?? o['store'] ?? '').toString();
    final price = (o['price'] ?? 0) as num;
    final offerId =
        o['id'] is int ? o['id'] as int : int.tryParse('${o['id']}');
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.roseBg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(store,
                    style: const TextStyle(fontWeight: FontWeight.w700)),
                const SizedBox(height: 2),
                Text(formatSar(price),
                    style: const TextStyle(color: AppColors.maroon)),
              ],
            ),
          ),
          ElevatedButton(
            onPressed:
                offerId == null ? null : () => _acceptOffer(offerId),
            child: const Text('قبول'),
          ),
        ],
      ),
    );
  }
}
