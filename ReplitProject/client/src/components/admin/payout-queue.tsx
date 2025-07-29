import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Users } from "lucide-react";

export default function PayoutQueue() {
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const { data: payouts, isLoading } = useQuery({
    queryKey: ["/api/admin/payouts", { status: "pending" }],
  });

  const processAllMutation = useMutation({
    mutationFn: async () => {
      const response = await apiRequest("POST", "/api/admin/payouts/process-all", {});
      return response.json();
    },
    onSuccess: (data) => {
      toast({
        title: "Payouts processed",
        description: `Successfully processed ${data.processed} payouts`,
      });
      queryClient.invalidateQueries({ queryKey: ["/api/admin/payouts"] });
    },
    onError: (error: any) => {
      toast({
        title: "Processing failed",
        description: error.message || "Failed to process payouts",
        variant: "destructive",
      });
    },
  });

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
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg font-semibold">Pending Payouts</CardTitle>
          <Button 
            size="sm" 
            className="bg-green-600 hover:bg-green-700"
            onClick={() => processAllMutation.mutate()}
            disabled={processAllMutation.isPending || !payouts?.length}
          >
            {processAllMutation.isPending ? "Processing..." : "Process All"}
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {payouts?.length ? (
            payouts.map((payout: any) => (
              <div key={payout.id} className="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                <div className="flex items-center">
                  <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                    <Users className="text-gray-600 h-4 w-4" />
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">
                      {payout.worker?.username || "Unknown Worker"}
                    </p>
                    <p className="text-sm text-gray-500">
                      {payout.worker?.role === "worker" ? "Freelancer" : "Worker"}
                    </p>
                  </div>
                </div>
                <div className="text-right">
                  <p className="text-sm font-semibold text-gray-900">
                    ${parseFloat(payout.amount).toFixed(2)}
                  </p>
                  <p className="text-xs text-gray-500">
                    {payout.status === "pending" ? "Ready for payout" : "Pending review"}
                  </p>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-8 text-gray-500">
              <Users className="mx-auto h-12 w-12 text-gray-300 mb-4" />
              <p>No pending payouts</p>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
