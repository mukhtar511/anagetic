import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/network/api_client.dart';
import '../../core/theme/app_colors.dart';
import '../../core/utils/arabic_numbers.dart';
import '../../data/api_repository.dart';

/// SPEC §3.4 — product listing form.
class ListingScreen extends ConsumerStatefulWidget {
  const ListingScreen({super.key});

  @override
  ConsumerState<ListingScreen> createState() => _ListingScreenState();
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

const _conditions = <_Option>[
  _Option('new', 'جديد'),
  _Option('used_excellent', 'مستعمل حالة ممتازة'),
  _Option('used_verygood', 'جيدة جدًا'),
  _Option('used_good', 'جيدة'),
];

const _scopes = <_Option>[
  _Option('region', 'منطقتي'),
  _Option('all', 'كل المناطق'),
];

const _durations = <int>[3, 7, 15];

// Featured price matrix (SPEC §4): منطقتي 29/49/79 · كل المناطق 59/99/149.
const _featuredPrices = <String, Map<int, int>>{
  'region': {3: 29, 7: 49, 15: 79},
  'all': {3: 59, 7: 99, 15: 149},
};

class _ListingScreenState extends ConsumerState<ListingScreen> {
  final _titleCtrl = TextEditingController();
  final _descCtrl = TextEditingController();
  final _readyPriceCtrl = TextEditingController();
  final _customPriceCtrl = TextEditingController();
  final _rentPriceCtrl = TextEditingController();
  final _depositCtrl = TextEditingController();

  String _category = 'dress';
  String _condition = 'new';

  bool _sellReady = false;
  bool _sellCustom = false;
  bool _rent = false;

  bool _featured = false;
  String _featuredScope = 'region';
  int _featuredDays = 3;

  bool _returnAck = false;

  bool _aiLoading = false;
  bool _submitting = false;
  String? _aiSummary;

  @override
  void initState() {
    super.initState();
    for (final c in [
      _titleCtrl,
      _readyPriceCtrl,
      _customPriceCtrl,
      _rentPriceCtrl,
    ]) {
      c.addListener(_syncPreview);
    }
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _readyPriceCtrl.dispose();
    _customPriceCtrl.dispose();
    _rentPriceCtrl.dispose();
    _depositCtrl.dispose();
    super.dispose();
  }

  void _syncPreview() {
    if (mounted) setState(() {});
  }

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _aiFill() async {
    setState(() => _aiLoading = true);
    try {
      final data = await ref.read(apiRepositoryProvider).aiFill();
      if (!mounted) return;
      setState(() {
        final title = data['title'];
        if (title is String && title.isNotEmpty) _titleCtrl.text = title;
        final desc = data['description'];
        if (desc is String && desc.isNotEmpty) _descCtrl.text = desc;
        final category = data['category'];
        if (category is String &&
            _categories.any((c) => c.value == category)) {
          _category = category;
        }
        // color returned by aiFill — surfaced via the summary line.
        final summary = data['summary'];
        _aiSummary = summary is String
            ? summary
            : (data['color'] != null
                ? 'اللون المقترح: ${data['color']}'
                : null);
      });
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _aiLoading = false);
    }
  }

  List<Map<String, dynamic>> _buildModes() {
    final modes = <Map<String, dynamic>>[];
    if (_sellReady) {
      modes.add({
        'type': 'ready',
        'price': num.tryParse(_readyPriceCtrl.text.trim()) ?? 0,
      });
    }
    if (_sellCustom) {
      modes.add({
        'type': 'custom',
        'price': num.tryParse(_customPriceCtrl.text.trim()) ?? 0,
      });
    }
    if (_rent) {
      modes.add({
        'type': 'rent',
        'price': num.tryParse(_rentPriceCtrl.text.trim()) ?? 0,
        'deposit': num.tryParse(_depositCtrl.text.trim()) ?? 0,
      });
    }
    return modes;
  }

  num? get _previewPrice {
    if (_sellReady) return num.tryParse(_readyPriceCtrl.text.trim());
    if (_sellCustom) return num.tryParse(_customPriceCtrl.text.trim());
    if (_rent) return num.tryParse(_rentPriceCtrl.text.trim());
    return null;
  }

  Future<void> _submit() async {
    final title = _titleCtrl.text.trim();
    if (title.isEmpty) {
      _snack('اكتبي اسم المنتج أولًا');
      return;
    }
    final modes = _buildModes();
    if (modes.isEmpty) {
      _snack('⚠ لازم تفعّلين خيار واحد على الأقل');
      return;
    }
    if (!_returnAck) {
      _snack('⚠ لازم تقرّين بسياسة الاسترجاع المجاني قبل النشر');
      return;
    }
    setState(() => _submitting = true);
    try {
      final body = <String, dynamic>{
        'title': title,
        'category': _category,
        'condition': _condition,
        'description': _descCtrl.text.trim(),
        'return_policy_ack': true,
        'modes': modes,
        if (_featured)
          'featured': {
            'scope': _featuredScope,
            'days': _featuredDays,
          },
      };
      await ref.read(apiRepositoryProvider).createProduct(body);
      if (!mounted) return;
      _snack('✓ تم نشر إعلانك!');
      context.pop();
    } on ApiException catch (e) {
      _snack(e.message);
    } catch (_) {
      _snack('حدث خطأ، حاولي مرة ثانية');
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('نشر منتج')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _photosSection(),
          const SizedBox(height: 16),
          _detailsSection(),
          const SizedBox(height: 16),
          _modesSection(),
          const SizedBox(height: 16),
          _featuredSection(),
          const SizedBox(height: 16),
          _returnSection(),
          const SizedBox(height: 16),
          _previewCard(),
          const SizedBox(height: 20),
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
                  : const Text('نشر الإعلان'),
            ),
          ),
          const SizedBox(height: 24),
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

  Widget _photosSection() {
    return _card(
      title: '📸 صور المنتج',
      children: [
        SizedBox(
          height: 90,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: 4,
            separatorBuilder: (_, __) => const SizedBox(width: 10),
            itemBuilder: (_, i) => Container(
              width: 90,
              decoration: BoxDecoration(
                color: AppColors.roseBg,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: AppColors.roseSoft),
              ),
              alignment: Alignment.center,
              child: Icon(
                i == 0 ? Icons.add_a_photo_outlined : Icons.image_outlined,
                color: AppColors.mutedInk,
              ),
            ),
          ),
        ),
        const SizedBox(height: 12),
        OutlinedButton.icon(
          onPressed: _aiLoading ? null : _aiFill,
          icon: _aiLoading
              ? const SizedBox(
                  height: 16,
                  width: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.auto_awesome, size: 18),
          label: const Text('✨ تعبئة تلقائية من الصورة'),
          style: OutlinedButton.styleFrom(
            foregroundColor: AppColors.maroon,
            side: const BorderSide(color: AppColors.roseSoft),
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          ),
        ),
        if (_aiSummary != null) ...[
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.warnBg,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(_aiSummary!,
                style: const TextStyle(
                    fontSize: 12.5, color: AppColors.warnInk)),
          ),
        ],
      ],
    );
  }

  Widget _detailsSection() {
    return _card(
      title: '📝 بيانات المنتج',
      children: [
        TextField(
          controller: _titleCtrl,
          decoration: const InputDecoration(labelText: 'اسم المنتج *'),
        ),
        const SizedBox(height: 14),
        DropdownButtonFormField<String>(
          initialValue: _category,
          isExpanded: true,
          decoration: const InputDecoration(labelText: 'القسم *'),
          items: [
            for (final c in _categories)
              DropdownMenuItem(value: c.value, child: Text(c.label)),
          ],
          onChanged: (v) => v == null ? null : setState(() => _category = v),
        ),
        const SizedBox(height: 14),
        DropdownButtonFormField<String>(
          initialValue: _condition,
          isExpanded: true,
          decoration: const InputDecoration(labelText: 'حالة المنتج'),
          items: [
            for (final c in _conditions)
              DropdownMenuItem(value: c.value, child: Text(c.label)),
          ],
          onChanged: (v) => v == null ? null : setState(() => _condition = v),
        ),
        const SizedBox(height: 14),
        TextField(
          controller: _descCtrl,
          maxLines: 3,
          decoration: const InputDecoration(labelText: 'وصف المنتج'),
        ),
      ],
    );
  }

  Widget _modesSection() {
    return _card(
      title: '🏷️ خيارات البيع والتأجير',
      children: [
        const Text('فعّلي خيارًا واحدًا على الأقل',
            style: TextStyle(fontSize: 12.5, color: AppColors.mutedInk)),
        const SizedBox(height: 6),
        SwitchListTile(
          value: _sellReady,
          onChanged: (v) => setState(() => _sellReady = v),
          title: const Text('بيع بدون تعديل'),
          contentPadding: EdgeInsets.zero,
          activeThumbColor: AppColors.maroon,
        ),
        if (_sellReady)
          _priceField(_readyPriceCtrl, 'سعر البيع *'),
        SwitchListTile(
          value: _sellCustom,
          onChanged: (v) => setState(() => _sellCustom = v),
          title: const Text('بيع بتعديل'),
          contentPadding: EdgeInsets.zero,
          activeThumbColor: AppColors.maroon,
        ),
        if (_sellCustom)
          _priceField(_customPriceCtrl, 'سعر البيع مع التعديل *'),
        SwitchListTile(
          value: _rent,
          onChanged: (v) => setState(() => _rent = v),
          title: const Text('تأجير'),
          contentPadding: EdgeInsets.zero,
          activeThumbColor: AppColors.maroon,
        ),
        if (_rent) ...[
          _priceField(_rentPriceCtrl, 'سعر التأجير / ٣ أيام *'),
          _priceField(_depositCtrl, 'التأمين الشامل *'),
        ],
      ],
    );
  }

  Widget _priceField(TextEditingController ctrl, String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: TextField(
        controller: ctrl,
        keyboardType: const TextInputType.numberWithOptions(decimal: true),
        decoration: InputDecoration(labelText: label, suffixText: 'ر.س'),
      ),
    );
  }

  Widget _featuredSection() {
    final price = _featuredPrices[_featuredScope]?[_featuredDays];
    return _card(
      title: '⭐ نوع الإعلان',
      children: [
        RadioGroup<bool>(
          groupValue: _featured,
          onChanged: (v) => setState(() => _featured = v ?? false),
          child: const Column(
            children: [
              RadioListTile<bool>(
                value: false,
                title: Text('عادي مجاني'),
                contentPadding: EdgeInsets.zero,
                activeColor: AppColors.maroon,
              ),
              RadioListTile<bool>(
                value: true,
                title: Text('⭐ مميز'),
                contentPadding: EdgeInsets.zero,
                activeColor: AppColors.maroon,
              ),
            ],
          ),
        ),
        if (_featured) ...[
          const SizedBox(height: 8),
          const Text('النطاق',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            children: [
              for (final s in _scopes)
                ChoiceChip(
                  label: Text(s.label),
                  selected: _featuredScope == s.value,
                  onSelected: (_) => setState(() => _featuredScope = s.value),
                  selectedColor: AppColors.maroon,
                  labelStyle: TextStyle(
                    color: _featuredScope == s.value
                        ? Colors.white
                        : AppColors.ink,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          const Text('المدة',
              style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
          const SizedBox(height: 6),
          Wrap(
            spacing: 8,
            children: [
              for (final d in _durations)
                ChoiceChip(
                  label: Text('${d.toString().toArabicDigits()} أيام'),
                  selected: _featuredDays == d,
                  onSelected: (_) => setState(() => _featuredDays = d),
                  selectedColor: AppColors.maroon,
                  labelStyle: TextStyle(
                    color:
                        _featuredDays == d ? Colors.white : AppColors.ink,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'الأسعار: منطقتي ٢٩/٤٩/٧٩ · كل المناطق ٥٩/٩٩/١٤٩ ر.س',
            style: TextStyle(fontSize: 12, color: AppColors.mutedInk),
          ),
          if (price != null) ...[
            const SizedBox(height: 6),
            Text('سعر باقتك: ${formatSar(price)}',
                style: const TextStyle(
                    fontWeight: FontWeight.w700, color: AppColors.maroon)),
          ],
        ],
      ],
    );
  }

  Widget _returnSection() {
    return _card(
      title: '↩️ سياسة الاسترجاع',
      children: [
        CheckboxListTile(
          value: _returnAck,
          onChanged: (v) => setState(() => _returnAck = v ?? false),
          title: const Text(
              'أُقر بسياسة الاسترجاع المجاني وأني راعيت تكلفته في تسعيري'),
          contentPadding: EdgeInsets.zero,
          controlAffinity: ListTileControlAffinity.leading,
          activeColor: AppColors.maroon,
        ),
      ],
    );
  }

  Widget _previewCard() {
    final title = _titleCtrl.text.trim();
    final price = _previewPrice;
    final categoryLabel = _categories
        .firstWhere((c) => c.value == _category,
            orElse: () => _categories.first)
        .label;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.roseBg,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.roseSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('👁️ كذا يظهر إعلانك للعميلات',
              style: TextStyle(
                  fontWeight: FontWeight.w700, color: AppColors.maroon)),
          const SizedBox(height: 10),
          Text(
            title.isEmpty ? 'اسم المنتج' : title,
            style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
          ),
          const SizedBox(height: 4),
          Text(categoryLabel,
              style:
                  const TextStyle(fontSize: 12.5, color: AppColors.mutedInk)),
          const SizedBox(height: 6),
          Text(
            price == null ? 'حددي السعر' : formatSar(price),
            style: const TextStyle(
                fontWeight: FontWeight.w700, color: AppColors.maroon),
          ),
        ],
      ),
    );
  }
}
