export type ExtractedDocumentMetadata = {
    doc_number: null | string;
    progress_percent: null | number;
    pic_owner: null | string;
    pic_pmc: null | string;
    pic_contractor: null | string;
};

export type ExtractedDocumentItem = {
    description: string;
    unit: null | string;
    volume: null | number;
    unit_price: null | number;
    total: null | number;
};

export type ExtractedDocumentSubCategory = {
    name: string;
    items: ExtractedDocumentItem[];
};

export type ExtractedDocumentCategory = {
    category: string;
    sub_categories: ExtractedDocumentSubCategory[];
};

export type ExtractedDocumentPreviewField = {
    label: string;
    value: null | number | string;
};

export type ExtractedDocumentPreviewGroup = {
    category: string;
    description: string;
    target_tables: string[];
    fields: ExtractedDocumentPreviewField[];
};

export type ExtractedDocumentSchema = {
    metadata: ExtractedDocumentMetadata;
    grouping_results: ExtractedDocumentCategory[];
    preview_groups: ExtractedDocumentPreviewGroup[];
};

const emptyMetadata = (): ExtractedDocumentMetadata => ({
    doc_number: null,
    progress_percent: null,
    pic_owner: null,
    pic_pmc: null,
    pic_contractor: null,
});

const normalizeText = (text: string): string =>
    text
        .replace(/\r/g, '\n')
        .replace(/\t/g, '  ')
        .split('\n')
        .map((line) => line.trimEnd())
        .join('\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();

const parseIndonesianNumber = (value: null | string): null | number => {
    if (!value) {
        return null;
    }

    const cleaned = value
        .replace(/[^\d,.-]/g, '')
        .replace(/\.(?=\d{3}(\D|$))/g, '')
        .replace(',', '.');

    const parsed = Number.parseFloat(cleaned);

    return Number.isFinite(parsed) ? parsed : null;
};

const cleanPersonName = (value: null | string): null | string => {
    if (!value) {
        return null;
    }

    const cleaned = value
        .replace(/\b(NIP|NRP|Jabatan|Nama|Pihak|Owner|PMC|Kontraktor)\b.*$/i, '')
        .replace(/^[\s:.-]+|[\s:.-]+$/g, '')
        .trim();

    return cleaned.length > 0 ? cleaned : null;
};

const firstMatch = (text: string, patterns: RegExp[]): null | string => {
    for (const pattern of patterns) {
        const match = text.match(pattern);

        if (match?.[1]) {
            return match[1].trim();
        }
    }

    return null;
};

export const extractImportantDocumentData = (rawText: string): ExtractedDocumentSchema => {
    const text = normalizeText(rawText);
    const metadata = emptyMetadata();

    metadata.doc_number = firstMatch(text, [
        /(?:Nomor|No\.?|Number)\s*[:.-]\s*([A-Z0-9./,\-% -]{8,})/i,
        /\b([0-9]{1,3}(?:\.[0-9]{1,3})?\/[A-Z]{2,}\/[A-Z0-9,\-%/-]+\/[IVXLCDM]+\/20\d{2})\b/i,
    ]);

    metadata.progress_percent = parseIndonesianNumber(firstMatch(text, [
        /(?:progress|progres|mc|mutual check)[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
        /(\d{1,3}(?:[,.]\d+)?)\s*%\s*(?:pekerjaan|progress|progres|mc)/i,
    ]));

    metadata.pic_owner = cleanPersonName(firstMatch(text, [
        /(?:owner|pemilik pekerjaan|pengguna jasa|ppk)[\s\S]{0,80}?(?:nama)?\s*[:.-]\s*([^\n]+)/i,
        /(?:Ir\.|Dr\.|H\.|Hj\.)?\s*([A-Z][A-Za-z.'\s]+)\s*(?:\n|\s)+(?:Owner|PPK|Pengguna Jasa)/i,
    ]));

    metadata.pic_pmc = cleanPersonName(firstMatch(text, [
        /(?:pmc|konsultan|manajemen konstruksi)[\s\S]{0,80}?(?:nama)?\s*[:.-]\s*([^\n]+)/i,
        /([A-Z][A-Za-z.'\s]+)\s*(?:\n|\s)+(?:PMC|Konsultan)/i,
    ]));

    metadata.pic_contractor = cleanPersonName(firstMatch(text, [
        /(?:kontraktor|penyedia jasa|pelaksana)[\s\S]{0,80}?(?:nama)?\s*[:.-]\s*([^\n]+)/i,
        /([A-Z][A-Za-z.'\s]+)\s*(?:\n|\s)+(?:Kontraktor|Penyedia Jasa|Pelaksana)/i,
    ]));

    return {
        metadata,
        grouping_results: extractGroupingResults(text),
        preview_groups: extractPreviewGroups(text, metadata),
    };
};

const categoryPattern = /^(?:\d+\.\s*)?([A-Z][A-Z\s/&()-]{4,})$/;
const itemPattern = /^(.+?)\s{2,}([A-Za-z][A-Za-z0-9/%.-]*)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)$/;

const extractGroupingResults = (text: string): ExtractedDocumentCategory[] => {
    const categories: ExtractedDocumentCategory[] = [];
    let currentCategory: null | ExtractedDocumentCategory = null;
    let currentSubCategory: null | ExtractedDocumentSubCategory = null;

    for (const rawLine of text.split('\n')) {
        const line = rawLine.trim();

        if (!line) {
            continue;
        }

        const categoryMatch = line.match(categoryPattern);

        if (categoryMatch?.[1] && !line.includes(':')) {
            currentCategory = {
                category: categoryMatch[1].replace(/\s+/g, ' '),
                sub_categories: [],
            };
            currentSubCategory = {
                name: 'Umum',
                items: [],
            };
            currentCategory.sub_categories.push(currentSubCategory);
            categories.push(currentCategory);
            continue;
        }

        const itemMatch = line.match(itemPattern);

        if (!itemMatch || !currentCategory || !currentSubCategory) {
            continue;
        }

        currentSubCategory.items.push({
            description: itemMatch[1].trim(),
            unit: itemMatch[2],
            volume: parseIndonesianNumber(itemMatch[3]),
            unit_price: parseIndonesianNumber(itemMatch[4]),
            total: parseIndonesianNumber(itemMatch[5]),
        });
    }

    return categories.filter((category) =>
        category.sub_categories.some((subCategory) => subCategory.items.length > 0),
    );
};

const extractPreviewGroups = (
    text: string,
    metadata: ExtractedDocumentMetadata,
): ExtractedDocumentPreviewGroup[] => [
    {
        category: 'BAMC / Mutual Check (MC)',
        description: 'Monitoring progress documents for progress reports and approvals.',
        target_tables: ['progress_reports', 'progress_approvals'],
        fields: [
            { label: 'Nomor BA', value: metadata.doc_number },
            { label: 'Progres Fisik (%)', value: metadata.progress_percent },
            {
                label: 'Tanggal Cut-off/Pemeriksaan',
                value: firstMatch(text, [
                    /(?:tanggal\s*(?:cut[- ]?off|pemeriksaan|progress|progres))\s*[:.-]\s*([^\n]+)/i,
                    /(?:pada\s+tanggal)\s+([0-9]{1,2}\s+[A-Za-z]+\s+20\d{2})/i,
                ]),
            },
            { label: 'Pihak Kesatu (Owner)', value: metadata.pic_owner },
            { label: 'Pihak Kedua (Konsultan)', value: metadata.pic_pmc },
            { label: 'Pihak Ketiga (Kontraktor)', value: metadata.pic_contractor },
            {
                label: 'Referensi Kontrak / SPK',
                value: firstMatch(text, [
                    /(?:nomor\s*)?(?:spk|kontrak|perjanjian)\s*[:.-]\s*([A-Z0-9./ -]{8,})/i,
                    /\b([0-9]{1,3}\.[0-9]{1,3}\/PK\/[A-Z0-9\s./-]+\/[IVXLCDM]+\/20\d{2})\b/i,
                ]),
            },
        ],
    },
    {
        category: 'Dokumen Kontrak (SPK / Addendum)',
        description: 'Contract basis for project records.',
        target_tables: ['projects'],
        fields: [
            {
                label: 'Nomor Kontrak/SPK',
                value: firstMatch(text, [
                    /(?:nomor\s*)?(?:spk|kontrak|perjanjian|addendum)\s*[:.-]\s*([A-Z0-9./ -]{8,})/i,
                    /\b([0-9]{1,3}\.[0-9]{1,3}\/PK\/[A-Z0-9\s./-]+\/[IVXLCDM]+\/20\d{2})\b/i,
                ]),
            },
            {
                label: 'Nama Pekerjaan',
                value: firstMatch(text, [
                    /(?:nama\s*)?(?:pekerjaan|paket pekerjaan)\s*[:.-]\s*([^\n]+)/i,
                    /(?:kegiatan)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
            {
                label: 'Nilai Kontrak',
                value: firstMatch(text, [
                    /(?:nilai\s*(?:kontrak|pekerjaan|spk))\s*[:.-]\s*(Rp\.?\s*[\d.,]+)/i,
                    /(Rp\.?\s*[\d.]+(?:,\d{2})?)/i,
                ]),
            },
            {
                label: 'Masa Pelaksanaan',
                value: firstMatch(text, [
                    /(?:masa\s*pelaksanaan)\s*[:.-]\s*([^\n]+)/i,
                    /(?:tanggal\s*mulai)[\s\S]{0,80}?(?:tanggal\s*berakhir|selesai)\s*[:.-]?\s*([^\n]+)/i,
                ]),
            },
            {
                label: 'Nama Vendor Pelaksana',
                value: firstMatch(text, [
                    /(PT\.?\s+Jasa\s+Tirta\s+Energi)/i,
                    /(?:penyedia jasa|vendor|kontraktor)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
            {
                label: 'Lokasi Proyek',
                value: firstMatch(text, [
                    /(?:lokasi(?:\s*proyek|\s*pekerjaan)?)\s*[:.-]\s*([^\n]+)/i,
                    /((?:Desa|Kelurahan|Kecamatan|Kabupaten)\s+[^\n]+)/i,
                ]),
            },
        ],
    },
    {
        category: 'Dokumen Pemeriksaan (BAHPP / C3)',
        description: 'Final inspection and acceptance validation.',
        target_tables: ['progress_approvals'],
        fields: [
            {
                label: 'Nomor BAHPP',
                value: firstMatch(text, [
                    /(?:nomor\s*)?(?:bahpp|c3)\s*[:.-]\s*([A-Z0-9./ -]{6,})/i,
                    /\b([A-Z0-9./-]*BAHPP[A-Z0-9./-]*\/20\d{2})\b/i,
                ]),
            },
            {
                label: 'Tanggal BAHPP',
                value: firstMatch(text, [
                    /(?:tanggal\s*(?:bahpp|pemeriksaan|penandatanganan))\s*[:.-]\s*([^\n]+)/i,
                    /(?:pada\s+hari\s+[A-Za-z]+,?\s+tanggal)\s+([^\n]+)/i,
                ]),
            },
            {
                label: 'Status Pemeriksaan',
                value: firstMatch(text, [
                    /\b(diterima\s+dengan\s+perbaikan|diterima|ditolak)\b/i,
                    /(?:status\s*pemeriksaan)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
            {
                label: 'Tim Pemeriksa',
                value: firstMatch(text, [
                    /(?:tim\s*pemeriksa)\s*[:.-]\s*([^\n]+)/i,
                    /(?:daftar\s*hadir|checklist)[\s\S]{0,120}?([A-Z][A-Za-z.'\s]+(?:,|\n)[\s\S]{0,80})/i,
                ]),
            },
            {
                label: 'Rekomendasi',
                value: firstMatch(text, [
                    /(?:rekomendasi|catatan teknis|saran)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
        ],
    },
    {
        category: 'Dokumen Teknis & RAB',
        description: 'Budget and technical document data for RAB/RAP details.',
        target_tables: ['rabs', 'rab_items', 'raps', 'rap_items'],
        fields: [
            {
                label: 'Tahun Anggaran',
                value: firstMatch(text, [
                    /(?:tahun\s*anggaran)\s*[:.-]\s*(20\d{2})/i,
                    /\bTA\.?\s*(20\d{2})\b/i,
                ]),
            },
            {
                label: 'Nama Kegiatan',
                value: firstMatch(text, [
                    /(?:nama\s*kegiatan|kegiatan)\s*[:.-]\s*([^\n]+)/i,
                    /(?:pekerjaan)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
            {
                label: 'Pemilik Proyek',
                value: firstMatch(text, [
                    /(Perum\s+Jasa\s+Tirta\s+I)/i,
                    /(?:pemilik\s*proyek|pengguna\s*jasa|owner)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
            { label: 'Mata Uang', value: text.match(/\bIDR\b|Rupiah|Rp\.?/i) ? 'IDR' : null },
            {
                label: 'Penyusun/Penawar',
                value: firstMatch(text, [
                    /(PT\.?\s+Jasa\s+Tirta\s+Energi)/i,
                    /(?:penyusun|penawar)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
        ],
    },
    {
        category: 'Dokumen Progress (Kurva S / Monitoring)',
        description: 'Planned versus actual progress monitoring.',
        target_tables: ['progress_reports'],
        fields: [
            {
                label: 'Periode Laporan',
                value: firstMatch(text, [
                    /(?:periode\s*laporan)\s*[:.-]\s*([^\n]+)/i,
                    /\b(Minggu\s+ke[- ]?\d+|Bulan\s+ke[- ]?\d+)\b/i,
                ]),
            },
            {
                label: 'Progres Rencana (%)',
                value: parseIndonesianNumber(firstMatch(text, [
                    /(?:progress|progres)\s*rencana[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                    /(?:rencana)[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                ])),
            },
            {
                label: 'Progres Realisasi (%)',
                value: parseIndonesianNumber(firstMatch(text, [
                    /(?:progress|progres)\s*realisasi[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                    /(?:realisasi|aktual)[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                ])),
            },
            {
                label: 'Deviasi (%)',
                value: parseIndonesianNumber(firstMatch(text, [
                    /(?:deviasi|selisih)[^\d-]{0,20}(-?\d{1,3}(?:[,.]\d+)?)\s*%/i,
                ])),
            },
        ],
    },
];
