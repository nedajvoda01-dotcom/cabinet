export function formatDate(input: string | number | Date | null | undefined) {
  if (!input) return "—";
  const d = new Date(input);
  return d.toLocaleString();
}
