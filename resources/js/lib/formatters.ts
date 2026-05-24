export const formatCurrency = (
    value: null | number | string | undefined,
    currency = 'IDR',
) =>
    value === null || value === undefined || value === ''
        ? '-'
        : new Intl.NumberFormat('id-ID', {
              style: 'currency',
              currency,
              maximumFractionDigits: 0,
          }).format(Number(value));
