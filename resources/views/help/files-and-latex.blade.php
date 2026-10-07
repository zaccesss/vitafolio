<x-help-page slug="files-and-latex">
    <h2>{{ __('Uploading a file') }}</h2>
    <p>{!! __('Attach a PDF or Word (.docx) file of up to 5 MB to any CV under <strong>File and LaTeX</strong>. Files are checked by their contents rather than their names, so a renamed file is refused. Visitors open PDFs in the browser and download Word files.') !!}</p>
    <p>{{ __('Each account has 100 MB of storage for CV files and project media. A meter on the page shows how much you have used. Profile photos and text do not count towards it.') }}</p>

    <h2>{{ __('Writing a CV in LaTeX') }}</h2>
    <p>{{ __('The LaTeX editor compiles your CV in the browser with pdfLaTeX, XeLaTeX or LuaLaTeX. Start from one of four templates (Classic, Compact, Modern or Academic), which fill in your details for you.') }}</p>
    <ul>
        <li>{!! __('<code>Ctrl+Enter</code> compiles and <code>Ctrl+S</code> saves. On a Mac, use <code>Cmd</code> instead of <code>Ctrl</code>.') !!}</li>
        <li>{!! __('The first compile downloads the LaTeX engine with the core packages, about 120 MB. After that your browser keeps it, so later compiles start straight away. Every starter template uses only the core packages. If you write your own, add <code>\\usepackage[T1]{fontenc}</code> and <code>\\usepackage{lmodern}</code> near the top: they keep bullet points and accented letters within the core download. If your own document uses a package outside them, the collection holding it downloads once, the first time it is needed.') !!}</li>
        <li>{{ __('Nothing leaves your device until you save. Saving stores your source and attaches the latest compiled PDF to the CV.') }}</li>
        <li>{!! __('If the code editor is awkward with a screen reader, tick <strong>Plain text box</strong> to switch to a standard text area.') !!}</li>
    </ul>
</x-help-page>
