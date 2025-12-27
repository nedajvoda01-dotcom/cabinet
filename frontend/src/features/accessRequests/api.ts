export const accessRequestsApi = {
  list: async () => [],
  approve: async (id: string) => ({ id, status: "approved" as const }),
  reject: async (id: string) => ({ id, status: "rejected" as const }),
};
