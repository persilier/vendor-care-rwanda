import React from 'react';
import { Button } from '@/components/ui/button';
import { Link } from 'react-router-dom';
import lightImage from "/images/error/light-401.png";
import darkImage from "/images/error/dark-401.png";
import { useTheme } from "next-themes";
import { authService } from '@/services/auth.service';

const UnauthorizedPage: React.FC = () => {
  const { theme } = useTheme();

  const handleLogin = () => {
    authService.logout(); // Clear any existing tokens
    window.location.href = '/login'; // Redirect to login
  };

  return (
    <div className="min-h-screen overflow-y-auto flex justify-center items-center p-10">
      <div className="w-full flex flex-col items-center">
        <div className="max-w-[740px]">
          <img
            src={theme === "dark" ? darkImage : lightImage}
            alt="unauthorized access image"
            className="w-full h-full object-cover"
          />
        </div>
        <div className="mt-16 text-center">
          <div className="text-2xl md:text-4xl lg:text-5xl font-semibold text-default-900">
            Unauthorized Access
          </div>
          <div className="mt-3 text-default-600 text-sm md:text-base">
            You must be logged in to access this page. <br />
            Your session may have expired or you lack the necessary credentials.
          </div>
          <div className="flex justify-center space-x-4 mt-9">
            <Button onClick={handleLogin} className="md:min-w-[200px]" size="lg">
              Log In
            </Button>
            <Button asChild variant="outline" className="md:min-w-[200px]" size="lg">
              <Link to="/">Go to Homepage</Link>
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UnauthorizedPage;
