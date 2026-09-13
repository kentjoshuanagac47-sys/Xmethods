@if (app()->getLocale() === 'ja')
    <style>
        #google_translate_element,
        .skiptranslate > iframe {
            display: none !important;
        }

        body {
            top: 0 !important;
        }
    </style>
    <div id="google_translate_element" aria-hidden="true"></div>
    <script>
        document.cookie = 'googtrans=/en/ja; path=/';
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: 'ja',
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit" defer></script>
@endif
