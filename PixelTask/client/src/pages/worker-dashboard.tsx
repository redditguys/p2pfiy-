import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Badge } from "@/components/ui/badge";
import { Home, LogOut, Upload, Wallet, DollarSign } from "lucide-react";
import { useAuth, authManager } from "@/lib/auth";
import { useLocation } from "wouter";
import { useEffect } from "react";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Task, Submission, Withdrawal, Transaction } from "@shared/schema";

export default function WorkerDashboard() {
  const [, setLocation] = useLocation();
  const { toast } = useToast();
  const { user, isAuthenticated } = useAuth();
  const [submitProofOpen, setSubmitProofOpen] = useState(false);
  const [withdrawalOpen, setWithdrawalOpen] = useState(false);
  const [selectedTask, setSelectedTask] = useState<Task | null>(null);

  useEffect(() => {
    if (!isAuthenticated || user?.role !== 'worker') {
      setLocation('/');
    }
  }, [isAuthenticated, user, setLocation]);

  // Form states
  const [proofForm, setProofForm] = useState({
    proofText: ''
  });

  const [withdrawalForm, setWithdrawalForm] = useState({
    amount: '',
    paymentMethod: '',
    paymentDetails: ''
  });

  // Fetch user data (with updated wallet balance)
  const { data: userData } = useQuery<{
    id: string;
    walletBalance: string;
    name: string;
    email: string;
    role: string;
  }>({
    queryKey: ['/api/users', user?.id],
    enabled: !!user?.id
  });

  // Fetch available tasks
  const { data: tasks = [] } = useQuery<Task[]>({
    queryKey: ['/api/tasks'],
  });

  // Fetch worker submissions
  const { data: submissions = [] } = useQuery<Submission[]>({
    queryKey: ['/api/submissions/worker', user?.id],
    enabled: !!user?.id
  });

  // Fetch withdrawals
  const { data: withdrawals = [] } = useQuery<Withdrawal[]>({
    queryKey: ['/api/withdrawals/user', user?.id],
    enabled: !!user?.id
  });

  // Fetch transactions
  const { data: transactions = [] } = useQuery<Transaction[]>({
    queryKey: ['/api/transactions/user', user?.id],
    enabled: !!user?.id
  });

  // Submit proof mutation
  const submitProofMutation = useMutation({
    mutationFn: async (submissionData: any) => {
      return apiRequest('POST', '/api/submissions', submissionData);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/submissions/worker', user?.id] });
      queryClient.invalidateQueries({ queryKey: ['/api/tasks'] });
      setSubmitProofOpen(false);
      setProofForm({ proofText: '' });
      setSelectedTask(null);
      toast({
        title: "Success",
        description: "Proof submitted successfully"
      });
    },
    onError: (error) => {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to submit proof",
        variant: "destructive"
      });
    }
  });

  // Request withdrawal mutation
  const withdrawalMutation = useMutation({
    mutationFn: async (withdrawalData: any) => {
      return apiRequest('POST', '/api/withdrawals', { ...withdrawalData, userId: user?.id });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/withdrawals/user', user?.id] });
      queryClient.invalidateQueries({ queryKey: ['/api/users', user?.id] });
      setWithdrawalOpen(false);
      setWithdrawalForm({ amount: '', paymentMethod: '', paymentDetails: '' });
      toast({
        title: "Success",
        description: "Withdrawal request submitted"
      });
    },
    onError: (error) => {
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to request withdrawal",
        variant: "destructive"
      });
    }
  });

  const handleApplyForTask = (task: Task) => {
    setSelectedTask(task);
    setSubmitProofOpen(true);
  };

  const handleSubmitProof = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedTask) return;

    submitProofMutation.mutate({
      taskId: selectedTask.id,
      workerId: user?.id,
      proofText: proofForm.proofText
    });
  };

  const handleWithdrawal = (e: React.FormEvent) => {
    e.preventDefault();
    withdrawalMutation.mutate(withdrawalForm);
  };

  const handleLogout = () => {
    authManager.logout();
    setLocation('/');
  };

  if (!isAuthenticated || user?.role !== 'worker') {
    return null;
  }

  const walletBalance = parseFloat(userData?.walletBalance || user?.walletBalance || "0");

  return (
    <div className="min-h-screen bg-minecraft-tan">
      {/* Header */}
      <div className="bg-minecraft-brown text-white p-4 border-b-4 border-minecraft-gray">
        <div className="flex items-center justify-between">
          <h2 className="font-pixel text-xl">Worker Dashboard</h2>
          <div className="flex items-center space-x-4">
            <div className="flex items-center space-x-2">
              <Wallet className="h-4 w-4" />
              <span className="font-pixel">${walletBalance.toFixed(2)}</span>
            </div>
            <span className="text-sm">Welcome, {user?.name}</span>
            <Button 
              onClick={() => setLocation('/')}
              variant="outline"
              className="border-white text-white hover:bg-white hover:text-minecraft-brown"
            >
              <Home className="mr-2 h-4 w-4" />
              Home
            </Button>
            <Button 
              onClick={handleLogout}
              className="bg-red-600 text-white border-2 border-white hover:bg-red-700"
            >
              <LogOut className="mr-2 h-4 w-4" />
              Logout
            </Button>
          </div>
        </div>
      </div>

      <div className="max-w-6xl mx-auto p-6">
        <Tabs defaultValue="tasks" className="space-y-6">
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="tasks">Available Tasks</TabsTrigger>
            <TabsTrigger value="submissions">My Submissions</TabsTrigger>
            <TabsTrigger value="wallet">Wallet</TabsTrigger>
            <TabsTrigger value="history">History</TabsTrigger>
          </TabsList>

          {/* Available Tasks */}
          <TabsContent value="tasks">
            <Card className="bg-white border-4 border-minecraft-brown">
              <CardHeader>
                <CardTitle className="font-pixel text-lg minecraft-brown">
                  Available Tasks ({tasks.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {tasks.map((task) => (
                    <div key={task.id} className="border-2 border-minecraft-gray p-4 hover:border-minecraft-purple transition-colors">
                      <div className="flex items-center justify-between mb-2">
                        <Badge variant="outline" className="minecraft-purple">
                          {task.category.toUpperCase()}
                        </Badge>
                        <span className="font-pixel minecraft-purple text-lg">
                          ${parseFloat(task.price).toFixed(2)}
                        </span>
                      </div>
                      
                      <h4 className="font-bold minecraft-brown mb-2">{task.title}</h4>
                      <p className="text-sm minecraft-gray mb-4">
                        {task.description.substring(0, 100)}...
                      </p>
                      
                      <div className="flex items-center justify-between text-xs minecraft-gray mb-4">
                        <span>{task.estimatedTime || 'Not specified'}</span>
                        <span>{task.spotsAvailable} spots left</span>
                      </div>
                      
                      <Button
                        onClick={() => handleApplyForTask(task)}
                        className="w-full bg-minecraft-highlight text-white border-2 border-minecraft-purple hover:bg-minecraft-purple"
                        disabled={task.spotsAvailable <= 0}
                      >
                        {task.spotsAvailable <= 0 ? 'NO SPOTS LEFT' : 'APPLY NOW'}
                      </Button>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {/* My Submissions */}
          <TabsContent value="submissions">
            <Card className="bg-white border-4 border-minecraft-brown">
              <CardHeader>
                <CardTitle className="font-pixel text-lg minecraft-brown">
                  My Submissions ({submissions.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                {submissions.length === 0 ? (
                  <p className="minecraft-gray">No submissions yet.</p>
                ) : (
                  <div className="space-y-4">
                    {submissions.map((submission) => (
                      <div key={submission.id} className="border-2 border-minecraft-gray p-4">
                        <div className="flex items-center justify-between mb-2">
                          <h4 className="font-bold minecraft-brown">Task ID: {submission.taskId}</h4>
                          <Badge 
                            variant={submission.status === 'approved' ? 'default' : 
                                   submission.status === 'rejected' ? 'destructive' : 'secondary'}
                          >
                            {submission.status.toUpperCase()}
                          </Badge>
                        </div>
                        <p className="text-sm minecraft-gray">
                          Submitted: {new Date(submission.submittedAt).toLocaleString()}
                        </p>
                        {submission.adminNotes && (
                          <p className="text-sm minecraft-gray mt-2">
                            Admin Notes: {submission.adminNotes}
                          </p>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>

          {/* Wallet */}
          <TabsContent value="wallet">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <Card className="bg-white border-4 border-minecraft-brown">
                <CardHeader>
                  <CardTitle className="font-pixel text-lg minecraft-brown flex items-center">
                    <Wallet className="mr-2" />
                    Wallet Balance
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-center">
                    <div className="font-pixel text-4xl minecraft-purple mb-4">
                      ${walletBalance.toFixed(2)}
                    </div>
                    <Button
                      onClick={() => setWithdrawalOpen(true)}
                      disabled={walletBalance < 3}
                      className="bg-minecraft-green minecraft-brown border-2 border-minecraft-brown hover:bg-green-400"
                    >
                      <DollarSign className="mr-2 h-4 w-4" />
                      Request Withdrawal
                    </Button>
                    {walletBalance < 3 && (
                      <p className="text-sm minecraft-gray mt-2">
                        Minimum withdrawal: $3.00
                      </p>
                    )}
                  </div>
                </CardContent>
              </Card>

              <Card className="bg-white border-4 border-minecraft-brown">
                <CardHeader>
                  <CardTitle className="font-pixel text-lg minecraft-brown">
                    Withdrawal History
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {withdrawals.length === 0 ? (
                    <p className="minecraft-gray">No withdrawals yet.</p>
                  ) : (
                    <div className="space-y-2">
                      {withdrawals.slice(0, 5).map((withdrawal) => (
                        <div key={withdrawal.id} className="flex items-center justify-between text-sm">
                          <span>${withdrawal.amount}</span>
                          <Badge variant="outline">
                            {withdrawal.status.toUpperCase()}
                          </Badge>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Transaction History */}
          <TabsContent value="history">
            <Card className="bg-white border-4 border-minecraft-brown">
              <CardHeader>
                <CardTitle className="font-pixel text-lg minecraft-brown">
                  Transaction History ({transactions.length})
                </CardTitle>
              </CardHeader>
              <CardContent>
                {transactions.length === 0 ? (
                  <p className="minecraft-gray">No transactions yet.</p>
                ) : (
                  <div className="space-y-4">
                    {transactions.map((transaction) => (
                      <div key={transaction.id} className="border-2 border-minecraft-gray p-4 flex items-center justify-between">
                        <div>
                          <h4 className="font-bold minecraft-brown">{transaction.description}</h4>
                          <p className="text-sm minecraft-gray">
                            {transaction.createdAt ? new Date(transaction.createdAt).toLocaleString() : 'N/A'}
                          </p>
                        </div>
                        <div className={`font-bold ${parseFloat(transaction.amount) > 0 ? 'text-green-600' : 'text-red-600'}`}>
                          {parseFloat(transaction.amount) > 0 ? '+' : ''}${transaction.amount}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>

      {/* Submit Proof Modal */}
      <Dialog open={submitProofOpen} onOpenChange={setSubmitProofOpen}>
        <DialogContent className="bg-white border-4 border-minecraft-brown max-w-md shadow-pixel">
          <DialogHeader>
            <DialogTitle className="font-pixel text-lg minecraft-brown">
              Submit Proof of Work
            </DialogTitle>
          </DialogHeader>
          
          {selectedTask && (
            <>
              <div className="mb-4">
                <h4 className="font-bold minecraft-brown">{selectedTask.title}</h4>
                <p className="text-sm minecraft-gray">Price: ${parseFloat(selectedTask.price).toFixed(2)}</p>
              </div>
              
              <form onSubmit={handleSubmitProof} className="space-y-4">
                <div>
                  <Label className="block text-sm font-bold minecraft-brown mb-2">Proof of Work</Label>
                  <Textarea
                    className="w-full border-2 border-minecraft-brown"
                    placeholder="Describe your completed work or provide links to your submission"
                    value={proofForm.proofText}
                    onChange={(e) => setProofForm({...proofForm, proofText: e.target.value})}
                    required
                  />
                </div>
                
                <Button 
                  type="submit" 
                  disabled={submitProofMutation.isPending}
                  className="w-full bg-minecraft-highlight text-white border-2 border-minecraft-purple hover:bg-minecraft-purple transition-all font-bold"
                >
                  <Upload className="mr-2 h-4 w-4" />
                  {submitProofMutation.isPending ? 'SUBMITTING...' : 'SUBMIT PROOF'}
                </Button>
              </form>
            </>
          )}
        </DialogContent>
      </Dialog>

      {/* Withdrawal Modal */}
      <Dialog open={withdrawalOpen} onOpenChange={setWithdrawalOpen}>
        <DialogContent className="bg-white border-4 border-minecraft-brown max-w-md shadow-pixel">
          <DialogHeader>
            <DialogTitle className="font-pixel text-lg minecraft-brown">
              Request Withdrawal
            </DialogTitle>
          </DialogHeader>
          
          <form onSubmit={handleWithdrawal} className="space-y-4">
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Amount ($)</Label>
              <Input
                type="number"
                step="0.01"
                min="3.00"
                max={walletBalance}
                className="w-full border-2 border-minecraft-brown"
                placeholder="3.00"
                value={withdrawalForm.amount}
                onChange={(e) => setWithdrawalForm({...withdrawalForm, amount: e.target.value})}
                required
              />
              <p className="text-xs minecraft-gray mt-1">
                Available balance: ${walletBalance.toFixed(2)}
              </p>
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Payment Method</Label>
              <Select value={withdrawalForm.paymentMethod} onValueChange={(value) => setWithdrawalForm({...withdrawalForm, paymentMethod: value})}>
                <SelectTrigger className="border-2 border-minecraft-brown">
                  <SelectValue placeholder="Select payment method" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="jazzcash">JazzCash</SelectItem>
                  <SelectItem value="easypaisa">Easypaisa</SelectItem>
                  <SelectItem value="paytm">Paytm</SelectItem>
                  <SelectItem value="usdt">USDT</SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <div>
              <Label className="block text-sm font-bold minecraft-brown mb-2">Payment Details</Label>
              <Input
                type="text"
                className="w-full border-2 border-minecraft-brown"
                placeholder="Phone number, wallet address, etc."
                value={withdrawalForm.paymentDetails}
                onChange={(e) => setWithdrawalForm({...withdrawalForm, paymentDetails: e.target.value})}
                required
              />
            </div>
            
            <Button 
              type="submit" 
              disabled={withdrawalMutation.isPending}
              className="w-full bg-minecraft-green minecraft-brown border-2 border-minecraft-brown hover:bg-green-400 transition-all font-bold"
            >
              {withdrawalMutation.isPending ? 'REQUESTING...' : 'REQUEST WITHDRAWAL'}
            </Button>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
