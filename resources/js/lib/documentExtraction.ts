export type ExtractedDocumentMetadata = {
    doc_number: null | string;
    contract_date: null | string;
    project_name: null | string;
    contract_value: null | number;
    location: null | string;
    owner: null | string;
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
    value: ExtractedDocumentPreviewValue;
};

export type ExtractedDocumentPreviewValue =
    | null
    | number
    | string
    | (number | string)[];

export type ExtractedDocumentPreviewGroup = {
    category: string;
    description: string;
    target_tables: string[];
    fields: ExtractedDocumentPreviewField[];
    confidence: number;
    is_recommended: boolean;
};

export type ExtractedDocumentSchema = {
    metadata: ExtractedDocumentMetadata;
    detected_category: null | string;
    grouping_results: ExtractedDocumentCategory[];
    preview_groups: ExtractedDocumentPreviewGroup[];
};

const emptyMetadata = (): ExtractedDocumentMetadata => ({
    doc_number: null,
    contract_date: null,
    project_name: null,
    contract_value: null,
    location: null,
    owner: null,
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

    const numeric = value.replace(/[^\d,.-]/g, '').replace(/^-+/, '-');

    if (!/\d/.test(numeric)) {
        return null;
    }

    const lastComma = numeric.lastIndexOf(',');
    const lastDot = numeric.lastIndexOf('.');
    const decimalIndex = Math.max(lastComma, lastDot);
    const hasDecimal =
        decimalIndex >= 0 && numeric.slice(decimalIndex + 1).length <= 2;
    const normalized = hasDecimal
        ? `${numeric.slice(0, decimalIndex).replace(/[,.]/g, '')}.${numeric.slice(decimalIndex + 1).replace(/[,.]/g, '')}`
        : numeric.replace(/[,.]/g, '');

    const parsed = Number.parseFloat(normalized);

    return Number.isFinite(parsed) ? parsed : null;
};

const cleanPersonName = (value: null | string): null | string => {
    if (!value) {
        return null;
    }

    const cleaned = value
        .replace(
            /\b(NIP|NRP|Jabatan|Nama|Pihak|Owner|PMC|Kontraktor)\b.*$/i,
            '',
        )
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

const uniqueValues = <T extends number | string>(values: (null | T)[]): T[] => {
    const seen = new Set<string>();
    const results: T[] = [];

    for (const value of values) {
        if (value === null || value === '') {
            continue;
        }

        const key = String(value).replace(/\s+/g, ' ').trim().toLowerCase();

        if (seen.has(key)) {
            continue;
        }

        seen.add(key);
        results.push(value);
    }

    return results;
};

const allMatches = (
    text: string,
    patterns: RegExp[],
    cleaner: (value: string) => null | string = (value) => value.trim(),
): string[] =>
    uniqueValues(
        patterns.flatMap((pattern) => {
            const flags = pattern.flags.includes('g')
                ? pattern.flags
                : `${pattern.flags}g`;
            const globalPattern = new RegExp(pattern.source, flags);

            return [...text.matchAll(globalPattern)].map((match) =>
                match[1] ? cleaner(match[1]) : null,
            );
        }),
    );

const previewValue = <T extends number | string>(
    values: T[],
): null | T | T[] => {
    if (values.length === 0) {
        return null;
    }

    return values.length === 1 ? values[0] : values;
};

const cleanDocumentNumber = (value: null | string): null | string => {
    if (!value) {
        return null;
    }

    const cleaned = value
        .replace(/[!|]/g, '/')
        .replace(/(?<=\d):(?=\d)/g, '.')
        .replace(/\/{2,}/g, '/')
        .replace(/\s*\/\s*/g, '/')
        .replace(/\s{2,}/g, ' ')
        .replace(/^[\s:.-]+|[\s:.-]+$/g, '')
        .trim();

    return cleaned.length > 0 ? cleaned : null;
};

const beginningOfDocument = (text: string): string =>
    text.split('\n').slice(0, 45).join('\n').slice(0, 3000);

const documentNumberPatterns = [
    /(?:Nom[o0]r|N[o0]\.?|Number)\s*[:;.\-]?\s*([A-Z0-9!|./,\-%: -]{8,})/i,
    /(?:Nom[o0]r|N[o0]\.?|Number)[^\n]*\n(?:[^\n]*\n){0,3}?\s*([0-9]{1,3}(?:\.[0-9]{1,3})?\/[A-Z]{2,}\/[A-Z0-9,\-%/ -]+\/[IVXLCDM]+\/20\d{2})\b/i,
    /\b([0-9]{1,3}(?:\.[0-9]{1,3})?\/[A-Z]{2,}\/[A-Z0-9,\-%/ -]+\/[IVXLCDM]+\/20\d{2})\b/i,
];

const extractDocumentNumbers = (text: string): string[] =>
    allMatches(
        text,
        documentNumberPatterns.map(
            (pattern) => new RegExp(pattern.source, 'gi'),
        ),
        cleanDocumentNumber,
    );

const extractCurrencyAmounts = (text: string): string[] =>
    allMatches(text, [
        /(?:sebesar|senilai|total(?:\s+harga)?(?:\s+penawaran)?|jumlah)\s*[:.-]?\s*(Rp\.?\s*[lI\d][lI\d.,]+)/gi,
        /(Rp\.?\s*[lI\d][lI\d.]+(?:,\d{2})?)/gi,
    ]);

const extractLocations = (text: string): string[] =>
    allMatches(
        text,
        [
            /(?:lokasi(?:\s*proyek|\s*pekerjaan)?|pada\s+lokasi)\s*[:.-]\s*([^\n.]+(?:\n[^\n.]+){0,2})/gi,
            /((?:Desa|Kelurahan|Kecamatan|Kabupaten|Kec\.?)\s+[^\n.]+)/gi,
        ],
        (value) =>
            value
                .replace(/\s+/g, ' ')
                .replace(/[.;,]\s*$/g, '')
                .trim(),
    );

const extractWorkPackages = (text: string): string[] =>
    allMatches(
        text,
        [
            /(?:pekerjaan|perihal|tentang)\s*[:.-]\s*([^\n]+(?:\n(?!\d+\.|Nama|Jabatan|Pada hari)[^\n]+){0,2})/gi,
            /(Pembangunan\s+Perkuatan\s+Tanggul\s+Muara\s+Sungai\s+(?:Mandosi|Bolon)[^\n.,;]*)/gi,
            /(Pengadaan\s+AWLR[^\n]*)/gi,
        ],
        (value) => value.replace(/\s+/g, ' ').trim(),
    );

const extractProjectName = (text: string): null | string => {
    const value = firstMatch(text, [
        /Perihal\s*:\s*(?:Penawaran\s+Harga\s*)?([\s\S]+?)(?:\n\s*Sehubungan\b|\n\s*Kepada\b|\n\s*Lampiran\b)/i,
        /Pekerjaan\s*:\s*([\s\S]+?)(?:\n\s*Lokasi\b|\n\s*Pemilik\b|\n\s*SATUAN\b|\n\s*NO\.)/i,
        /(?:pekerjaan|perihal|tentang)\s*[:.-]\s*([^\n]+)/i,
    ]);

    return (
        value
            ?.replace(/^Penawaran\s+Harga\s+/i, '')
            ?.replace(/\s+/g, ' ')
            .replace(/[.;,\sr]+$/g, '')
            .trim() || null
    );
};

const extractProjectLocation = (
    text: string,
    projectName: null | string,
): null | string => {
    const explicitLocation = firstMatch(text, [
        /Lokasi\s*[:.-]\s*([^\n]+(?:\n(?!Pemilik|SATUAN|VOLUME|HARGA|NO\.)[^\n]+){0,2})/i,
        /((?:Muara|Desa|Kelurahan|Kecamatan|Kec\.|Kabupaten|Kota)\s+[^\n]+(?:\n(?!Pemilik|SATUAN|VOLUME|HARGA|NO\.)[^\n]+){0,2})/i,
    ]);

    if (explicitLocation) {
        return explicitLocation
            .replace(/\s+/g, ' ')
            .replace(/[.;,]\s*$/g, '')
            .trim();
    }

    const locationFromName = projectName?.split(':').slice(1).join(':').trim();

    return locationFromName &&
        /desa|kec\.?|kabupaten|kota|muara/i.test(locationFromName)
        ? locationFromName
        : null;
};

const normalizeOcrCurrency = (value: null | string): null | string =>
    value
        ?.replace(/\bRp\s*[lI]{2}(?=[\d.])/gi, 'Rp11')
        .replace(/\bRp\s*[lI](?=[\d.])/gi, 'Rp1') ?? null;

const extractContractValue = (text: string): null | number => {
    const totals = [
        ...text.matchAll(
            /TOTAL\s*\(INCLUDING\s+PPN\s*12%\)\s*\n?\s*([\d.,]+)/gi,
        ),
    ]
        .map((match) => parseIndonesianNumber(match[1]))
        .filter((value): value is number => value !== null);

    if (totals.length > 0) {
        return Math.max(...totals);
    }

    return parseIndonesianNumber(
        normalizeOcrCurrency(
            firstMatch(text, [
                /(?:total\s+harga\s+penawaran|harga\s+penawaran|penawaran)[\s\S]{0,180}?(Rp\.?\s*[lI\d][lI\d.,]+)/i,
                /sebesar\s*\n?\s*(Rp\.?\s*[lI\d][lI\d.,]+)/i,
                /(?:nilai\s+kontrak|total\s+pekerjaan\s+konsolidasi|pembulatan)\s*[:.-]?\s*([\d.,]+)/i,
            ]),
        ),
    );
};

const extractProgressBreakdown = (text: string): string[] =>
    uniqueValues([
        ...[
            ...text.matchAll(
                /Progres\s+(.+?)\s+sebesar\s+(\d{1,3}(?:[,.]\d+)?)\s*%/gi,
            ),
        ].map(
            (match) =>
                `${match[1].replace(/\s+/g, ' ').trim()}: ${match[2].replace(',', '.')}%`,
        ),
        ...[
            ...text.matchAll(
                /(Kurva\s*S|Progres\s+Aktual|Progres\s+Rencana)\s*[:.-]\s*(\d{1,3}(?:[,.]\d+)?)\s*%/gi,
            ),
        ].map(
            (match) =>
                `${match[1].replace(/\s+/g, ' ').trim()}: ${match[2].replace(',', '.')}%`,
        ),
    ]);

const extractNamedPeople = (text: string): string[] =>
    uniqueValues(
        [
            ...text.matchAll(
                /(?:\d+\.\s*)?Nama\s*:?\s*([^\n]+)[\s\S]{0,80}?Jabatan\s*:?\s*([^\n]+)/gi,
            ),
        ].map((match) => {
            const name = cleanPersonName(match[1]);
            const role = match[2]
                ?.replace(/\s+/g, ' ')
                .replace(/[.;,]\s*$/g, '')
                .trim();

            return name ? `${name}${role ? ` - ${role}` : ''}` : null;
        }),
    );

export const extractImportantDocumentData = (
    rawText: string,
    sourceName = '',
): ExtractedDocumentSchema => {
    const text = normalizeText(rawText);
    const documentBeginning = beginningOfDocument(text);
    const classificationText = normalizeText(`${sourceName}\n${text}`);
    const metadata = emptyMetadata();

    metadata.doc_number = cleanDocumentNumber(
        firstMatch(documentBeginning, documentNumberPatterns) ??
            firstMatch(text, documentNumberPatterns),
    );
    metadata.contract_date = firstMatch(documentBeginning, [
        /Tanggal\s*[:.-]\s*([^\n]+)/i,
        /(?:Malang|Jakarta|Surabaya|Bandung|Semarang),\s*([0-9]{1,2}\s+[A-Za-z]+\s+20\d{2})/i,
    ]);
    metadata.project_name = extractProjectName(text);
    metadata.location = extractProjectLocation(text, metadata.project_name);
    metadata.contract_value = extractContractValue(text);
    metadata.owner = firstMatch(text, [
        /Pemilik\s*[:.-]\s*([^\n]+)/i,
        /(Perum\s+Jasa\s+Tirta\s+I)/i,
    ]);

    metadata.progress_percent = parseIndonesianNumber(
        firstMatch(text, [
            /(?:progress|progres|mc|mutual check)[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
            /(\d{1,3}(?:[,.]\d+)?)\s*%\s*(?:pekerjaan|progress|progres|mc)/i,
        ]),
    );

    metadata.pic_owner = cleanPersonName(
        firstMatch(text, [
            /(?:owner|pemilik pekerjaan|pengguna jasa|ppk)[\s\S]{0,80}?(?:nama)?\s*[:.-]\s*([^\n]+)/i,
            /(?:Ir\.|Dr\.|H\.|Hj\.)?\s*([A-Z][A-Za-z.'\s]+)\s*(?:\n|\s)+(?:Owner|PPK|Pengguna Jasa)/i,
        ]),
    );

    metadata.pic_pmc = cleanPersonName(
        firstMatch(text, [
            /(?:pmc|konsultan|manajemen konstruksi)[\s\S]{0,80}?(?:nama)?\s*[:.-]\s*([^\n]+)/i,
            /([A-Z][A-Za-z.'\s]+)\s*(?:\n|\s)+(?:PMC|Konsultan)/i,
        ]),
    );

    metadata.pic_contractor = cleanPersonName(
        firstMatch(text, [
            /(?:kontraktor|penyedia jasa|pelaksana)[\s\S]{0,80}?(?:nama)?\s*[:.-]\s*([^\n]+)/i,
            /([A-Z][A-Za-z.'\s]+)\s*(?:\n|\s)+(?:Kontraktor|Penyedia Jasa|Pelaksana)/i,
        ]),
    );

    const previewGroups = rankPreviewGroups(
        extractPreviewGroups(text, metadata),
        classificationText,
    );

    return {
        metadata,
        detected_category: previewGroups[0]?.category ?? null,
        grouping_results: extractGroupingResults(text),
        preview_groups: previewGroups,
    };
};

const categoryPattern =
    /^(?:(?:\d+(?:\.\d+)*|[A-Z])\.\s*)?([A-Z][A-Z0-9\s/&(),.-]{4,})$/;
const itemPattern =
    /^(?:(?:\d+(?:\.\d+)*|[A-Z])\.?\s+)?(.+?)\s{2,}([A-Za-z0-9/%.,-]+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)$/;
const romanPattern = /^(?:[IVXLCDM]+)$/i;
const tableNoisePattern =
    /^(?:NO\.?|URAIAN PEKERJAAN|SATUAN|VOLUME|HARGA SATUAN|TOTAL HARGA|LOKASI|PEMILIK)$/i;
const totalLinePattern =
    /^(?:TOTAL|PEMBULATAN|DPP|PPN|TERBILANG|PASAL|PIHAK\b)/i;
const unitPattern =
    /^(?:ls|cross|buah|unit|set|m'?|m2|m3|m\^?2|m\^?3|\$?m\{?\d?\}?\$?|kg|sample|batang)$/i;

const normalizeUnit = (value: string): string =>
    value
        .replace(/\$/g, '')
        .replace(/[{}]/g, '')
        .replace(/^m2$/i, 'm2')
        .replace(/^m3$/i, 'm3')
        .trim();

const isNumberLine = (line: string): boolean =>
    /^[\d.,]+$/.test(line.trim()) && parseIndonesianNumber(line) !== null;

const isRowNumberLine = (line: string): boolean =>
    /^\d{1,2}$/.test(line.trim());

const isUnitLine = (line: string): boolean => unitPattern.test(line.trim());

const isUpperHeading = (line: string): boolean => {
    const cleaned = line.replace(/[^A-Za-z0-9\s/&(),.-]/g, '').trim();

    return (
        cleaned.length >= 5 &&
        cleaned === cleaned.toUpperCase() &&
        /[A-Z]/.test(cleaned) &&
        !tableNoisePattern.test(cleaned) &&
        !totalLinePattern.test(cleaned)
    );
};

const ensureCategory = (
    categories: ExtractedDocumentCategory[],
    categoryName: string,
): ExtractedDocumentCategory => {
    const category = categories.find((item) => item.category === categoryName);

    if (category) {
        return category;
    }

    const created = {
        category: categoryName,
        sub_categories: [],
    };
    categories.push(created);

    return created;
};

const ensureSubCategory = (
    category: ExtractedDocumentCategory,
    subCategoryName: string,
): ExtractedDocumentSubCategory => {
    const subCategory = category.sub_categories.find(
        (item) => item.name === subCategoryName,
    );

    if (subCategory) {
        return subCategory;
    }

    const created = {
        name: subCategoryName,
        items: [],
    };
    category.sub_categories.push(created);

    return created;
};

const parseOcrBudgetItem = (lines: string[]): null | ExtractedDocumentItem => {
    const unitIndex = lines.findIndex(isUnitLine);

    if (unitIndex < 1) {
        return null;
    }

    const description = lines
        .slice(0, unitIndex)
        .filter(
            (line) =>
                !tableNoisePattern.test(line) && !totalLinePattern.test(line),
        )
        .join(' ')
        .replace(/\s+/g, ' ')
        .trim();

    if (!description || isUpperHeading(description)) {
        return null;
    }

    const numericValues = lines
        .slice(unitIndex + 1)
        .filter(isNumberLine)
        .map(parseIndonesianNumber)
        .filter((value): value is number => value !== null);

    if (numericValues.length < 2) {
        return null;
    }

    const [volume, unitPrice, total] = numericValues;

    return {
        description,
        unit: normalizeUnit(lines[unitIndex]),
        volume: volume ?? null,
        unit_price: unitPrice ?? null,
        total: total ?? (volume && unitPrice ? volume * unitPrice : null),
    };
};

const extractOcrBudgetRows = (text: string): ExtractedDocumentCategory[] => {
    const categories: ExtractedDocumentCategory[] = [];
    const lines = text
        .split('\n')
        .map((line) => line.trim())
        .filter(Boolean);
    let currentCategory = ensureCategory(categories, 'Dokumen Teknis & RAB');
    let currentSubCategory = ensureSubCategory(currentCategory, 'Umum');

    for (let index = 0; index < lines.length; index++) {
        const line = lines[index];
        const nextLine = lines[index + 1] ?? '';

        if (totalLinePattern.test(line) || tableNoisePattern.test(line)) {
            continue;
        }

        if (isUpperHeading(line) && !isRowNumberLine(line)) {
            if (/PEMBANGUNAN|PENGADAAN|PEKERJAAN KONSOLIDASI/i.test(line)) {
                currentCategory = ensureCategory(
                    categories,
                    line.replace(/\s+/g, ' '),
                );
                currentSubCategory = ensureSubCategory(currentCategory, 'Umum');
            } else if (!romanPattern.test(line)) {
                currentSubCategory = ensureSubCategory(
                    currentCategory,
                    line.replace(/\s+/g, ' '),
                );
            }

            continue;
        }

        if (romanPattern.test(line) && isUpperHeading(nextLine)) {
            currentSubCategory = ensureSubCategory(
                currentCategory,
                nextLine.replace(/\s+/g, ' '),
            );
            index++;
            continue;
        }

        if (!isRowNumberLine(line)) {
            continue;
        }

        const block: string[] = [];

        for (let cursor = index + 1; cursor < lines.length; cursor++) {
            const cursorLine = lines[cursor];

            if (
                isRowNumberLine(cursorLine) ||
                totalLinePattern.test(cursorLine) ||
                tableNoisePattern.test(cursorLine) ||
                (romanPattern.test(cursorLine) &&
                    isUpperHeading(lines[cursor + 1] ?? '')) ||
                (isUpperHeading(cursorLine) && !block.some(isUnitLine))
            ) {
                break;
            }

            block.push(cursorLine);
        }

        const item = parseOcrBudgetItem(block);

        if (item) {
            currentSubCategory.items.push(item);
        }
    }

    return categories.filter((category) =>
        category.sub_categories.some(
            (subCategory) => subCategory.items.length > 0,
        ),
    );
};

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

    const rowBasedResults = categories.filter((category) =>
        category.sub_categories.some(
            (subCategory) => subCategory.items.length > 0,
        ),
    );
    const ocrRows = extractOcrBudgetRows(text);

    return ocrRows.length > rowBasedResults.length ? ocrRows : rowBasedResults;
};

const foundFieldCount = (group: ExtractedDocumentPreviewGroup): number =>
    group.fields.filter((field) => field.value !== null && field.value !== '')
        .length;

const weightedMatches = (
    text: string,
    rules: {
        pattern: RegExp;
        weight: number;
    }[],
): number =>
    rules.reduce(
        (score, rule) => score + (rule.pattern.test(text) ? rule.weight : 0),
        0,
    );

const scorePreviewGroup = (
    group: ExtractedDocumentPreviewGroup,
    text: string,
): number => {
    const contentScore = foundFieldCount(group) * 8;

    const typeScore = weightedMatches(
        text,
        {
            'BAMC / Mutual Check (MC)': [
                { pattern: /\b(?:ba\s*)?mc\b/i, weight: 35 },
                { pattern: /mutual\s*check/i, weight: 35 },
                {
                    pattern: /berita\s+acara\s+pemeriksaan\s+pekerjaan/i,
                    weight: 22,
                },
                {
                    pattern: /\bmc[-\s]*(?:\d{1,3}(?:[,.]\d+)?)\s*%/i,
                    weight: 24,
                },
            ],
            'Dokumen Kontrak (SPK / Addendum)': [
                { pattern: /\b(?:spk|kontrak|perjanjian)\b/i, weight: 32 },
                { pattern: /\baddendum\b/i, weight: 28 },
                { pattern: /masa\s+pelaksanaan/i, weight: 12 },
                { pattern: /nilai\s+kontrak/i, weight: 16 },
            ],
            'Dokumen Pemeriksaan (BAHPP / C3)': [
                { pattern: /\bbahpp\b/i, weight: 42 },
                { pattern: /\bc3\b/i, weight: 38 },
                {
                    pattern: /berita\s+acara\s+hasil\s+pemeriksaan/i,
                    weight: 30,
                },
                { pattern: /hasil\s+pemeriksaan\s+pekerjaan/i, weight: 18 },
            ],
            'Dokumen Penawaran Harga': [
                { pattern: /surat\s+penawaran\s+harga/i, weight: 42 },
                { pattern: /\bsph\b/i, weight: 30 },
                {
                    pattern: /lampiran\s+rincian\s+surat\s+penawaran/i,
                    weight: 28,
                },
                { pattern: /harga\s+penawaran/i, weight: 18 },
            ],
            'Dokumen Teknis & RAB': [
                { pattern: /\brab\b/i, weight: 42 },
                { pattern: /rencana\s+anggaran\s+biaya/i, weight: 38 },
                {
                    pattern: /daftar\s+(?:kuantitas|volume)\s+dan\s+harga/i,
                    weight: 22,
                },
                { pattern: /rekapitulasi/i, weight: 12 },
            ],
            'Dokumen Progress (Kurva S / Monitoring)': [
                { pattern: /kurva\s*s/i, weight: 36 },
                { pattern: /monitoring/i, weight: 20 },
                { pattern: /completion\s+report/i, weight: 34 },
                { pattern: /\bawlr\b/i, weight: 24 },
                {
                    pattern: /progres(?:s)?\s+(?:rencana|realisasi|aktual)/i,
                    weight: 20,
                },
            ],
        }[group.category] ?? [],
    );

    return contentScore + typeScore;
};

const rankPreviewGroups = (
    groups: ExtractedDocumentPreviewGroup[],
    classificationText: string,
): ExtractedDocumentPreviewGroup[] => {
    const scoredGroups = groups
        .map((group) => ({
            ...group,
            confidence: scorePreviewGroup(group, classificationText),
            is_recommended: false,
        }))
        .sort((left, right) => {
            const scoreDelta = right.confidence - left.confidence;

            return scoreDelta !== 0
                ? scoreDelta
                : foundFieldCount(right) - foundFieldCount(left);
        });

    if (scoredGroups[0]) {
        scoredGroups[0].is_recommended = scoredGroups[0].confidence > 0;
    }

    return scoredGroups.filter(
        (group) =>
            group.is_recommended ||
            group.confidence > 0 ||
            foundFieldCount(group) > 0,
    );
};

const extractPreviewGroups = (
    text: string,
    metadata: ExtractedDocumentMetadata,
): ExtractedDocumentPreviewGroup[] => [
    {
        category: 'BAMC / Mutual Check (MC)',
        description:
            'Monitoring progress documents for progress reports and approvals.',
        target_tables: ['progress_reports', 'progress_approvals'],
        fields: [
            { label: 'Nomor BA', value: metadata.doc_number },
            {
                label: 'Nomor Dokumen / Referensi',
                value: previewValue(extractDocumentNumbers(text)),
            },
            { label: 'Progres Fisik (%)', value: metadata.progress_percent },
            {
                label: 'Progres Per Pekerjaan',
                value: previewValue(extractProgressBreakdown(text)),
            },
            {
                label: 'Tanggal Cut-off/Pemeriksaan',
                value: firstMatch(text, [
                    /(?:tanggal\s*(?:cut[- ]?off|pemeriksaan|progress|progres))\s*[:.-]\s*([^\n]+)/i,
                    /(?:pada\s+tanggal)\s+([0-9]{1,2}\s+[A-Za-z]+\s+20\d{2})/i,
                ]),
            },
            { label: 'Pihak Kesatu (Owner)', value: metadata.pic_owner },
            { label: 'Pihak Kedua (Konsultan)', value: metadata.pic_pmc },
            {
                label: 'Pihak Ketiga (Kontraktor)',
                value: metadata.pic_contractor,
            },
            {
                label: 'Semua Pihak / Penandatangan',
                value: previewValue(extractNamedPeople(text)),
            },
            {
                label: 'Referensi Kontrak / SPK',
                value: previewValue(
                    allMatches(text, [
                        /(?:nomor\s*)?(?:spk|kontrak|perjanjian)\s*[:.-]\s*([A-Z0-9./ -]{8,})/i,
                        /\b([0-9]{1,3}\.[0-9]{1,3}\/PK\/[A-Z0-9\s./-]+\/[IVXLCDM]+\/20\d{2})\b/gi,
                    ]),
                ),
            },
        ],
        confidence: 0,
        is_recommended: false,
    },
    {
        category: 'Dokumen Kontrak (SPK / Addendum)',
        description: 'Contract basis for project records.',
        target_tables: ['projects'],
        fields: [
            {
                label: 'Nomor Kontrak/SPK',
                value: previewValue(extractDocumentNumbers(text)),
            },
            {
                label: 'Nama Pekerjaan',
                value: previewValue(extractWorkPackages(text)),
            },
            {
                label: 'Nilai Kontrak',
                value: previewValue(extractCurrencyAmounts(text)),
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
                value: previewValue(extractLocations(text)),
            },
        ],
        confidence: 0,
        is_recommended: false,
    },
    {
        category: 'Dokumen Pemeriksaan (BAHPP / C3)',
        description: 'Final inspection and acceptance validation.',
        target_tables: ['progress_approvals'],
        fields: [
            {
                label: 'Nomor BAHPP',
                value: previewValue(extractDocumentNumbers(text)),
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
                value: previewValue(extractNamedPeople(text)),
            },
            {
                label: 'Lokasi Pemeriksaan',
                value: previewValue(extractLocations(text)),
            },
            {
                label: 'Progress Pemeriksaan',
                value: previewValue(extractProgressBreakdown(text)),
            },
            {
                label: 'Rekomendasi',
                value: firstMatch(text, [
                    /(?:rekomendasi|catatan teknis|saran)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
        ],
        confidence: 0,
        is_recommended: false,
    },
    {
        category: 'Dokumen Penawaran Harga',
        description:
            'Offer letter and price proposal details before contract confirmation.',
        target_tables: ['project_documents', 'rabs', 'rab_items'],
        fields: [
            {
                label: 'Nomor Surat Penawaran',
                value: firstMatch(text, [
                    /(?:nomor|no\.?)\s*[:.-]\s*([A-Z0-9./,\-% -]{8,})/i,
                    /\b([0-9]{1,3}(?:\.[0-9]{1,3})?\/(?:SPH|JTE)[A-Z0-9./,\-% -]+\/20\d{2})\b/i,
                ]),
            },
            {
                label: 'Tanggal Surat',
                value: firstMatch(text, [
                    /(?:tanggal)\s*[:.-]\s*([^\n]+)/i,
                    /(?:pada\s+tanggal)\s+([0-9]{1,2}\s+[A-Za-z]+\s+20\d{2})/i,
                ]),
            },
            {
                label: 'Pekerjaan Ditawarkan',
                value: previewValue(extractWorkPackages(text)),
            },
            {
                label: 'Nilai Penawaran / Paket',
                value: previewValue(extractCurrencyAmounts(text)),
            },
            {
                label: 'Penawar',
                value: firstMatch(text, [
                    /(PT\.?\s+Jasa\s+Tirta\s+Energi)/i,
                    /(?:penawar|penyedia jasa)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
        ],
        confidence: 0,
        is_recommended: false,
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
                label: 'Nomor Kontrak/SPK',
                value: metadata.doc_number,
            },
            {
                label: 'Tanggal Kontrak',
                value: metadata.contract_date,
            },
            {
                label: 'Nama Kegiatan',
                value:
                    metadata.project_name ??
                    previewValue(extractWorkPackages(text)),
            },
            {
                label: 'Pemilik Proyek',
                value:
                    metadata.owner ??
                    firstMatch(text, [
                        /(Perum\s+Jasa\s+Tirta\s+I)/i,
                        /(?:pemilik\s*proyek|pengguna\s*jasa|owner)\s*[:.-]\s*([^\n]+)/i,
                    ]),
            },
            {
                label: 'Lokasi Proyek',
                value: metadata.location,
            },
            {
                label: 'Nilai Kontrak',
                value: metadata.contract_value,
            },
            {
                label: 'Mata Uang',
                value: text.match(/\bIDR\b|Rupiah|Rp\.?/i) ? 'IDR' : null,
            },
            {
                label: 'Penyusun/Penawar',
                value: firstMatch(text, [
                    /(PT\.?\s+Jasa\s+Tirta\s+Energi)/i,
                    /(?:penyusun|penawar)\s*[:.-]\s*([^\n]+)/i,
                ]),
            },
        ],
        confidence: 0,
        is_recommended: false,
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
                label: 'Progres Per Pekerjaan',
                value: previewValue(extractProgressBreakdown(text)),
            },
            {
                label: 'Progres Rencana (%)',
                value: parseIndonesianNumber(
                    firstMatch(text, [
                        /(?:progress|progres)\s*rencana[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                        /(?:rencana)[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                    ]),
                ),
            },
            {
                label: 'Progres Realisasi (%)',
                value: parseIndonesianNumber(
                    firstMatch(text, [
                        /(?:progress|progres)\s*realisasi[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                        /(?:realisasi|aktual)[^\d]{0,20}(\d{1,3}(?:[,.]\d+)?)\s*%/i,
                    ]),
                ),
            },
            {
                label: 'Deviasi (%)',
                value: parseIndonesianNumber(
                    firstMatch(text, [
                        /(?:deviasi|selisih)[^\d-]{0,20}(-?\d{1,3}(?:[,.]\d+)?)\s*%/i,
                    ]),
                ),
            },
        ],
        confidence: 0,
        is_recommended: false,
    },
];
