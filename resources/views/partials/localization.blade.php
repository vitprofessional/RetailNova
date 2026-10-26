<style>
html[lang="bn"] .invoice-items-table tbody td, html[lang="bn"] .thermal-item, html[lang="bn"] .thermal-item-serial { line-height: 1.6 !important; }
html[lang="bn"] th, html[lang="bn"] label, html[lang="bn"] .btn { white-space: normal; }
html[lang="bn"] .thermal-items-table th { overflow-wrap: anywhere; letter-spacing: 0; }
</style>
<script>
window.RNLocale = {{ Illuminate\Support\Js::from(app()->getLocale()) }};
window.RNDataTableLanguage = {{ Illuminate\Support\Js::from([
    'emptyTable'=>__('datatables.empty'), 'info'=>__('datatables.info'), 'infoEmpty'=>__('datatables.info_empty'),
    'infoFiltered'=>__('datatables.info_filtered'), 'lengthMenu'=>__('datatables.length'), 'loadingRecords'=>__('datatables.loading'),
    'processing'=>__('datatables.processing'), 'search'=>__('datatables.search'), 'zeroRecords'=>__('datatables.zero'),
    'paginate'=>['first'=>__('datatables.first'),'last'=>__('datatables.last'),'next'=>__('datatables.next'),'previous'=>__('datatables.previous')],
    'aria'=>['sortAscending'=>__('datatables.ascending'),'sortDescending'=>__('datatables.descending')],
]) }};
window.RNMessages = {{ Illuminate\Support\Js::from(array_replace_recursive(require lang_path('en/javascript.php'), app()->getLocale() === 'bn' ? require lang_path('bn/javascript.php') : [])) }};
window.RNTranslate = function (key, replacements) {
    var value = window.RNMessages[key] || key;
    Object.keys(replacements || {}).forEach(function (name) { value = value.split(':' + name).join(String(replacements[name])); });
    return value;
};
</script>
