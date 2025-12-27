import { workRoutes } from "./sections/work/routes";
import { teamRoutes } from "./sections/team/routes";
import { adminRoutes } from "./sections/admin/routes";
import { superadminRoutes } from "./sections/superadmin/routes";

export const routes = [...workRoutes, ...teamRoutes, ...adminRoutes, ...superadminRoutes];
