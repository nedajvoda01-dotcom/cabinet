export type AccessRequest = {
  id: string;
  email: string;
  status: "pending" | "approved" | "rejected";
};
