import { can } from "../shared/rbac/can";

describe("can", () => {
  it("allows superadmin", () => {
    expect(can("superadmin", "pipeline.view" as any)).toBe(true);
  });

  it("denies guest", () => {
    expect(can("guest", "pipeline.view" as any)).toBe(false);
  });
});
