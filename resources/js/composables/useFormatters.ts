export function useFormatters() {
    const formatPrice = (amount: number) =>
        new Intl.NumberFormat('en-ZA', {
            style: 'currency',
            currency: 'ZAR',
            minimumFractionDigits: 2,
        }).format(amount);

    const formatDate = (dateStr: string | null) => {
        if (!dateStr) return '—';
        return new Intl.DateTimeFormat('en-ZA', { dateStyle: 'medium' }).format(
            new Date(dateStr + 'T00:00:00'),
        );
    };

    return { formatPrice, formatDate };
}