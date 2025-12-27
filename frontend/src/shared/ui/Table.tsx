import React from "react";

export function Table({ headers, rows }: { headers: string[]; rows: React.ReactNode[][] }) {
  return (
    <table className="w-full text-sm border-collapse">
      <thead>
        <tr>
          {headers.map((h) => (
            <th key={h} className="text-left border-b border-slate-200 px-2 py-1">
              {h}
            </th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((cells, idx) => (
          <tr key={idx} className="border-b border-slate-100">
            {cells.map((c, i) => (
              <td key={i} className="px-2 py-1">
                {c}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}
