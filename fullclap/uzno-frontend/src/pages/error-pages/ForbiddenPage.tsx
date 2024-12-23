import React from "react";
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import lightImage from "/images/error/light-403.png";
import darkImage from "/images/error/dark-403.png";
import { useTheme } from "next-themes";

const ForbiddenPage: React.FC = () => {
  const { theme } = useTheme();

  return (
    <div className="min-h-screen overflow-y-auto flex justify-center items-center p-10">
      <div className="w-full flex flex-col items-center">
        <div className="max-w-[740px]">
          <img
            src={theme === "dark" ? darkImage : lightImage}
            alt="forbidden access image"
            className="w-full h-full object-cover"
          />
        </div>
        <div className="mt-16 text-center">
          <div className="text-2xl md:text-4xl lg:text-5xl font-semibold text-default-900">
            Access Forbidden
          </div>
          <div className="mt-3 text-default-600 text-sm md:text-base">
            You do not have permission to access this page. <br />
            Please contact your administrator if you believe this is an error.
          </div>
          <div className="flex justify-center space-x-4 mt-9">
            <Button asChild className="md:min-w-[200px]" size="lg">
              <Link to="/">Go to Homepage</Link>
            </Button>
            <Button
              asChild
              variant="outline"
              className="md:min-w-[200px]"
              size="lg"
            >
              <Link to="/support">Request Access</Link>
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ForbiddenPage;
