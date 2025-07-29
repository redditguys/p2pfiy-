import { useQuery, useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { LogOut, CheckCircle, XCircle, Eye } from "lucide-react";
import { useAuth, authManager } from "@/lib/auth";
import { useLocation } from "wouter";
import { useEffect } from "react";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";

export default function AdminDashboard() {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  const { user, isAuthenticated } = useAuth();

  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'admin') {
      setLocation('/');
    }
  }, [isAuthenticated, user, setLocation]);

  // Fetch admin stats
  const { data: stats } = useQuery<{
    totalTasks: number;
    activeWorkers: number;
    activeClients: number;
    totalEarnings: string;
  }>({
    queryKey: ['/api/admin/stats'],
  });

  // Fetch pending submissions
  const { data: pendingSubmissions = [] } = useQuery<Array<any>>({
    queryKey: ['/api/submissions/pending'],
  });

  // Fetch pending withdrawals
  const { data: pendingWithdrawals = [] } = useQuery<Array<any>>({
    queryKey: ['/api/withdrawals/pending'],
  });

  // Review submission mutation
  const reviewSubmissionMutation = useMutation({
    mutationFn: async ({ id, status, adminNotes }: { id: string; status: 'approved' | 'rejected'; adminNotes?: string }) => {
      return apiRequest('PATCH', `/api/submissions/${id}/review`, { status, adminNotes });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/submissions/pending'] });
      toast({
        title: "Success",
        description: "Submission reviewed successfully"
      });
    },
    onError: (error) => {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to review submission",
        variant: "destructive"
      });
    }
  });

  // Process withdrawal mutation
  const processWithdrawalMutation = useMutation({
    mutationFn: async ({ id, status, adminNotes }: { id: string; status: 'processing' | 'completed' | 'rejected'; adminNotes?: string }) => {
      return apiRequest('PATCH', `/api/withdrawals/${id}/process`, { status, adminNotes });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/withdrawals/pending'] });
      toast({
        title: "Success",
        description: "Withdrawal processed successfully"
      });
    },
    onError: (error) => {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to process withdrawal",
        variant: "destructive"
      });
    }
  });

  const handleLogout = () => {
    authManager.logout();
    setLocation('/');
  };

  if (!isAuthenticated || user?.role !== 'admin') {
    return null;
  }

  return (
    <div className="min-h-screen bg-minecraft-brown">
      {/* Admin Header */}
      <div className="bg-minecraft-gray text-white p-4 border-b-4 border-minecraft-brown">
        <div className="flex items-center justify-between">
          <h2 className="font-pixel text-xl">Admin Dashboard</h2>
          <Button 
            onClick={handleLogout}
            className="bg-red-600 text-white border-2 border-white hover:bg-red-700"
          >
            <LogOut className="mr-2 h-4 w-4" />
            LOGOUT
          </Button>
        </div>
      </div>

      <div className="p-6">
        {/* Admin Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <Card className="bg-white border-4 border-minecraft-brown text-center">
            <CardContent className="p-4">
              <div className="font-pixel text-2xl minecraft-purple mb-2">
                {stats?.totalTasks || 0}
              </div>
              <div className="minecraft-brown">Total Tasks</div>
            </CardContent>
          </Card>
          
          <Card className="bg-white border-4 border-minecraft-brown text-center">
            <CardContent className="p-4">
              <div className="font-pixel text-2xl minecraft-purple mb-2">
                {stats?.activeWorkers || 0}
              </div>
              <div className="minecraft-brown">Active Workers</div>
            </CardContent>
          </Card>
          
          <Card className="bg-white border-4 border-minecraft-brown text-center">
            <CardContent className="p-4">
              <div className="font-pixel text-2xl minecraft-purple mb-2">
                {stats?.activeClients || 0}
              </div>
              <div className="minecraft-brown">Active Clients</div>
            </CardContent>
          </Card>
          
          <Card className="bg-white border-4 border-minecraft-brown text-center">
            <CardContent className="p-4">
              <div className="font-pixel text-2xl minecraft-purple mb-2">
                ${stats?.totalEarnings || '0'}
              </div>
              <div className="minecraft-brown">Total Earnings</div>
            </CardContent>
          </Card>
        </div>

        {/* Pending Submissions */}
        <Card className="bg-white border-4 border-minecraft-brown mb-8">
          <CardHeader>
            <CardTitle className="font-pixel text-lg minecraft-brown">
              Pending Submissions ({pendingSubmissions.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {pendingSubmissions.length === 0 ? (
              <p className="minecraft-gray">No pending submissions</p>
            ) : (
              <div className="space-y-4">
                {pendingSubmissions.map((submission: any) => (
                  <div key={submission.id} className="border-2 border-minecraft-gray p-4 flex items-center justify-between">
                    <div className="flex-1">
                      <h4 className="font-bold minecraft-brown">{submission.task.title}</h4>
                      <p className="text-sm minecraft-gray">
                        Worker: {submission.worker.name} | 
                        Submitted: {new Date(submission.submittedAt).toLocaleString()}
                      </p>
                      <p className="text-sm minecraft-gray">Task Value: ${submission.task.price}</p>
                      {submission.proofText && (
                        <p className="text-sm minecraft-gray mt-2">
                          Proof: {submission.proofText.substring(0, 100)}...
                        </p>
                      )}
                    </div>
                    <div className="flex space-x-2 ml-4">
                      <Button
                        size="sm"
                        className="bg-green-600 text-white border border-green-700 hover:bg-green-700"
                        onClick={() => reviewSubmissionMutation.mutate({ 
                          id: submission.id, 
                          status: 'approved' 
                        })}
                      >
                        <CheckCircle className="h-4 w-4" />
                      </Button>
                      <Button
                        size="sm"
                        className="bg-red-600 text-white border border-red-700 hover:bg-red-700"
                        onClick={() => reviewSubmissionMutation.mutate({ 
                          id: submission.id, 
                          status: 'rejected',
                          adminNotes: 'Submission rejected by admin'
                        })}
                      >
                        <XCircle className="h-4 w-4" />
                      </Button>
                      <Button
                        size="sm"
                        className="bg-blue-600 text-white border border-blue-700 hover:bg-blue-700"
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Withdrawal Requests */}
        <Card className="bg-white border-4 border-minecraft-brown">
          <CardHeader>
            <CardTitle className="font-pixel text-lg minecraft-brown">
              Withdrawal Requests ({pendingWithdrawals.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {pendingWithdrawals.length === 0 ? (
              <p className="minecraft-gray">No pending withdrawals</p>
            ) : (
              <div className="space-y-4">
                {pendingWithdrawals.map((withdrawal: any) => (
                  <div key={withdrawal.id} className="border-2 border-minecraft-gray p-4 flex items-center justify-between">
                    <div className="flex-1">
                      <h4 className="font-bold minecraft-brown">
                        {withdrawal.user.name} | ${withdrawal.amount}
                      </h4>
                      <p className="text-sm minecraft-gray">
                        Method: {withdrawal.paymentMethod.toUpperCase()} ({withdrawal.paymentDetails}) | 
                        Requested: {new Date(withdrawal.requestedAt).toLocaleString()}
                      </p>
                    </div>
                    <div className="flex space-x-2 ml-4">
                      <Button
                        size="sm"
                        className="bg-green-600 text-white border border-green-700 hover:bg-green-700"
                        onClick={() => processWithdrawalMutation.mutate({ 
                          id: withdrawal.id, 
                          status: 'completed' 
                        })}
                      >
                        PROCESS
                      </Button>
                      <Button
                        size="sm"
                        className="bg-yellow-600 text-white border border-yellow-700 hover:bg-yellow-700"
                        onClick={() => processWithdrawalMutation.mutate({ 
                          id: withdrawal.id, 
                          status: 'processing' 
                        })}
                      >
                        HOLD
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
