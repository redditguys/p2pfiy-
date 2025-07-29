import { Link } from "wouter";
import { Button } from "@/components/ui/button";
import { Box, User, Briefcase } from "lucide-react";
import { useAuth } from "@/lib/auth";

interface NavbarProps {
  onWorkerModalOpen: () => void;
  onClientModalOpen: () => void;
}

export default function Navbar({ onWorkerModalOpen, onClientModalOpen }: NavbarProps) {
  const { user, isAuthenticated } = useAuth();

  return (
    <header className="bg-minecraft-brown shadow-pixel border-b-4 border-minecraft-gray">
      <div className="max-w-6xl mx-auto px-4 py-4">
        <div className="flex items-center justify-between">
          {/* Logo */}
          <Link href="/">
            <div className="flex items-center space-x-3 cursor-pointer">
              <div className="w-12 h-12 bg-minecraft-purple border-2 border-white flex items-center justify-center">
                <Box className="text-white text-xl" />
              </div>
              <div>
                <h1 className="font-pixel text-white text-xl">P2PFIY</h1>
                <p className="minecraft-tan text-xs">Micro-Task Platform</p>
              </div>
            </div>
          </Link>

          {/* Navigation */}
          <nav className="hidden md:flex items-center space-x-6">
            <Link href="/" className="text-white hover:minecraft-highlight transition-colors">
              Tasks
            </Link>
            <a href="#" className="text-white hover:minecraft-highlight transition-colors">
              How It Works
            </a>
            <a href="#" className="text-white hover:minecraft-highlight transition-colors">
              Support
            </a>
          </nav>

          {/* Auth Buttons */}
          <div className="flex items-center space-x-3">
            {isAuthenticated ? (
              <div className="flex items-center space-x-4">
                <span className="text-white font-pixel text-sm">
                  {user?.name}
                </span>
                {user?.role === 'client' && (
                  <Link href="/client-dashboard">
                    <Button className="bg-minecraft-green minecraft-brown border-2 border-minecraft-brown hover:bg-green-400 transition-all transform hover:scale-105 shadow-pixel hover:shadow-pixel-hover">
                      Dashboard
                    </Button>
                  </Link>
                )}
                {user?.role === 'worker' && (
                  <Link href="/worker-dashboard">
                    <Button className="bg-minecraft-highlight text-white border-2 border-minecraft-purple hover:bg-minecraft-purple transition-all transform hover:scale-105 shadow-pixel hover:shadow-pixel-hover">
                      Dashboard
                    </Button>
                  </Link>
                )}
                {user?.role === 'admin' && (
                  <Link href="/admin-dashboard">
                    <Button className="bg-red-600 text-white border-2 border-red-700 hover:bg-red-700 transition-all transform hover:scale-105 shadow-pixel hover:shadow-pixel-hover">
                      Admin
                    </Button>
                  </Link>
                )}
              </div>
            ) : (
              <>
                <Button 
                  onClick={onWorkerModalOpen}
                  className="bg-minecraft-highlight text-white border-2 border-white hover:bg-minecraft-purple transition-all transform hover:scale-105 shadow-pixel hover:shadow-pixel-hover"
                >
                  <User className="mr-2 h-4 w-4" />
                  Join as Worker
                </Button>
                <Button 
                  onClick={onClientModalOpen}
                  className="bg-minecraft-green minecraft-brown border-2 border-minecraft-brown hover:bg-green-400 transition-all transform hover:scale-105 shadow-pixel hover:shadow-pixel-hover"
                >
                  <Briefcase className="mr-2 h-4 w-4" />
                  Post Tasks
                </Button>
              </>
            )}
          </div>
        </div>
      </div>
    </header>
  );
}
