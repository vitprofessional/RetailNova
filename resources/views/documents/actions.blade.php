<dialog class="rn-document-sheet" data-rn-document-sheet aria-labelledby="rnDocumentSheetTitle">
    <form method="dialog" class="rn-document-sheet__inner">
        <button class="rn-document-sheet__close" value="cancel" aria-label="{{ __('common.close') }}">×</button>
        <h2 id="rnDocumentSheetTitle">{{ __('documents.paper_size') }}</h2>
        <fieldset>
            <legend class="visually-hidden">{{ __('documents.paper_size') }}</legend>
            <label><input type="radio" name="paper" value="a4" @checked($paper === 'a4')> {{ __('documents.paper_a4') }}</label>
            <label><input type="radio" name="paper" value="thermal80" @checked($paper === 'thermal80')> {{ __('documents.paper_thermal80') }}</label>
            <label><input type="radio" name="paper" value="thermal58" @checked($paper === 'thermal58')> {{ __('documents.paper_thermal58') }}</label>
        </fieldset>
        <fieldset class="rn-document-sheet__formats" aria-label="{{ __('documents.share_format') }}">
            <legend>{{ __('documents.share_format') }}</legend>
            <label><input type="radio" name="share-format" value="pdf" checked> PDF</label>
            <label><input type="radio" name="share-format" value="png"> {{ __('documents.image') }}</label>
        </fieldset>
        <div class="rn-document-sheet__actions">
            <button type="button" class="rn-document-sheet__share" data-document-share data-ready-label="{{ __('documents.share') }}" data-building-label="{{ __('documents.preparing') }}" disabled>{{ __('documents.preparing') }}</button>
            <button type="button" class="rn-document-sheet__secondary" data-document-print>{{ __('common.print') }}</button>
            <button type="button" class="rn-document-sheet__secondary" data-document-pdf>{{ __('documents.save_pdf') }}</button>
            <button type="button" class="rn-document-sheet__secondary" data-document-png>{{ __('documents.save_image') }}</button>
        </div>
    </form>
</dialog>
