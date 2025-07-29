import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useQuery } from "@tanstack/react-query";
import { Users } from "lucide-react";

export default function UserActivity() {
  const { data: users, isLoading } = useQuery({
    queryKey: ["/api/admin/users"],
  });

  const recentActivity = [
    {
      type: "registration",
      user: "jessica.martinez@company.com",
      role: "client",
      time: "2 min ago",
      description: "New Client Registration",
    },
    {
      type: "verification",
      user: "tom.baker@freelancer.com",
      role: "worker",
      time: "15 min ago",
      description: "Profile Verification",
    },
    {
      type: "suspension",
      user: "suspicious.user@email.com",
      role: "suspended",
      time: "1 hour ago",
      description: "Account Suspended",
    },
  ];

  const getRoleBadge = (role: string) => {
    const roleConfig = {
      client: { className: "bg-green-100 text-green-800" },
      worker: { className: "bg-blue-100 text-blue-800" },
      admin: { className: "bg-purple-100 text-purple-800" },
      suspended: { className: "bg-red-100 text-red-800" },
    } as const;

    const config = roleConfig[role as keyof typeof roleConfig] || roleConfig.client;
    
    return (
      <Badge variant="secondary" className={config.className}>
        {role.charAt(0).toUpperCase() + role.slice(1)}
      </Badge>
    );
  };

  if (isLoading) {
    return (
      <Card>
        <CardContent className="p-6">
          <div className="animate-pulse space-y-4">
            <div className="h-4 bg-gray-200 rounded w-1/2"></div>
            <div className="space-y-3">
              {[...Array(3)].map((_, i) => (
                <div key={i} className="h-12 bg-gray-200 rounded"></div>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-lg font-semibold">Recent User Activity</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {recentActivity.map((activity, index) => (
            <div key={index} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
              <div className="flex items-center">
                <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                  <Users className="text-gray-600 h-4 w-4" />
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-gray-900">{activity.description}</p>
                  <p className="text-sm text-gray-500">{activity.user}</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-sm text-gray-900">{activity.time}</p>
                {getRoleBadge(activity.role)}
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
