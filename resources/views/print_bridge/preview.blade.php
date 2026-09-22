@extends('layouts.admin')

@section('titles')
    <title>Keysoft - Print Preview</title>
@endsection

@section('content')
    <div class="px-lg-5 py-lg-3 p-3">
        <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center py-2 text-md-start">
            <div class="flex-grow-1 mb-2 mb-md-0">
                <h1 class="h3 fw-bold mb-1">Print Preview</h1>
                <div class="text-muted">{{ $report->title }}</div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ $downloadUrl }}" id="download-pdf" class="btn btn-alt-primary" target="_blank" rel="noopener">
                    <i class="fa fa-download me-1"></i> Download PDF
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body bg-body-light">
                <div id="pdf-loading" class="py-5 text-center">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <div>Loading PDF preview...</div>
                </div>
                <div id="pdf-error" class="alert alert-danger d-none mb-0"></div>
                <div id="pdf-preview" class="d-flex flex-column align-items-center gap-3"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pdfjs/pdf.min.js') }}"></script>
    <script>
        (function() {
            const pdfBase64 = @json($pdfData);
            const cleanupUrl = @json($cleanupUrl);
            const csrfToken = @json(csrf_token());
            let cleaned = false;
            let isDownloading = false;

            pdfjsLib.GlobalWorkerOptions.workerSrc = @json(asset('js/pdfjs/pdf.worker.min.js'));

            async function renderPdf() {
                const loading = document.getElementById('pdf-loading');
                const error = document.getElementById('pdf-error');
                const container = document.getElementById('pdf-preview');

                try {
                    const binary = atob(pdfBase64);
                    const pdfBytes = new Uint8Array(binary.length);
                    for (let index = 0; index < binary.length; index++) {
                        pdfBytes[index] = binary.charCodeAt(index);
                    }

                    const pdf = await pdfjsLib.getDocument({
                        data: pdfBytes
                    }).promise;

                    for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                        const page = await pdf.getPage(pageNumber);
                        const baseViewport = page.getViewport({ scale: 1 });
                        const availableWidth = Math.min(container.clientWidth || 1100, 1100);
                        const scale = availableWidth / baseViewport.width;
                        const viewport = page.getViewport({ scale });
                        const pixelRatio = window.devicePixelRatio || 1;
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');

                        canvas.width = Math.floor(viewport.width * pixelRatio);
                        canvas.height = Math.floor(viewport.height * pixelRatio);
                        canvas.style.width = `${Math.floor(viewport.width)}px`;
                        canvas.style.height = `${Math.floor(viewport.height)}px`;
                        canvas.className = 'bg-white shadow-sm mw-100';
                        container.appendChild(canvas);

                        await page.render({
                            canvasContext: context,
                            viewport,
                            transform: pixelRatio === 1
                                ? null
                                : [pixelRatio, 0, 0, pixelRatio, 0, 0]
                        }).promise;
                    }

                    loading.classList.add('d-none');
                } catch (exception) {
                    loading.classList.add('d-none');
                    error.textContent = 'Failed to load PDF preview: ' + exception.message;
                    error.classList.remove('d-none');
                }
            }

            function cleanupTempFile() {
                if (isDownloading) return;
                if (cleaned) return;
                cleaned = true;

                const formData = new FormData();
                formData.append('_token', csrfToken);

                if (navigator.sendBeacon) {
                    navigator.sendBeacon(cleanupUrl, formData);
                    return;
                }

                fetch(cleanupUrl, {
                    method: 'POST',
                    body: formData,
                    keepalive: true,
                    credentials: 'same-origin'
                }).catch(function() {});
            }

            document.getElementById('download-pdf').addEventListener('click', function() {
                isDownloading = true;
                setTimeout(function() {
                    isDownloading = false;
                }, 5000);
            });

            renderPdf();
            window.addEventListener('pagehide', cleanupTempFile);
            window.addEventListener('beforeunload', cleanupTempFile);
        })();
    </script>
@endsection
