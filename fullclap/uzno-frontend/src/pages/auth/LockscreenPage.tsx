import React, { useState, useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import * as z from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { toast } from "sonner";
import { useNavigate } from "react-router-dom";
import { CountdownProgress } from "@/components/ui/countdown-progress";
import LockscreenService from "@/services/lockscreen.service";
import { authService } from "@/services/auth.service";
import axios from "axios";
import AuthLayout from "@/layouts/AuthLayout";

const schema = z.object({
  password: z.string().min(1, "Password is required"),
});

type FormData = z.infer<typeof schema>;

const LockscreenPage: React.FC = () => {
  const navigate = useNavigate();
  const [countdown, setCountdown] = useState(30);
  const [lockSessionId, setLockSessionId] = useState<string | null>(null);

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  } = useForm<FormData>({
    resolver: zodResolver(schema),
    mode: "all",
  });

  useEffect(() => {
    const checkLockStatus = async () => {
      try {
        // Check if user is authenticated
        const currentUser = authService.getCurrentUser();
        if (!currentUser) {
          console.log("No current user, redirecting to login");
          navigate("/login");
          return;
        }

        const status = await LockscreenService.checkLockStatus();
        console.log("Lock status:", status);

        if (!status.is_locked) {
          console.log("Not locked, redirecting to home");
          navigate("/");
          return;
        }

        if (status.lock_session) {
          setLockSessionId(status.lock_session.id);
          startCountdown();
        }
      } catch (error) {
        // Handle authentication or network errors
        console.error("Lock status check error:", error);
        if (axios.isAxiosError(error) && error.response?.status === 401) {
          toast.error("Session expired. Please log in again.");
          authService.logout();
          navigate("/login");
        } else {
          toast.error("Failed to check lock status");
        }
      }
    };

    checkLockStatus();
  }, [navigate]);

  useEffect(() => {
    if (countdown === 0) {
      toast.info("Unlock time expired. Redirecting to lockscreen.");
      navigate("/lockscreen");
    }
  }, [countdown, navigate]);

  const startCountdown = () => {
    const timer = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
  };

  const onSubmit = async (data: FormData) => {
    if (!lockSessionId) {
      toast.error("No active lock session");
      return;
    }

    try {
      await LockscreenService.unlock(lockSessionId, data.password);
      toast.success("Screen unlocked successfully");
      navigate("/");
      return;
    } catch (error) {
      // Handle specific unlock errors
      if (axios.isAxiosError(error)) {
        const status = error.response?.status;
        const message = error.response?.data?.message;

        if (status === 401) {
          toast.error("Invalid credentials");
        } else if (status === 400 && message) {
          toast.error(message);
        } else {
          toast.error("Unlock failed. Please try again.");
        }
      } else {
        toast.error("An unexpected error occurred");
      }
      reset();
    }
  };

  const handleLock = async () => {
    try {
      const lockSession = await LockscreenService.lock();
      setLockSessionId(lockSession.session_id);
      toast.info("Screen locked");
      startCountdown();
    } catch (error) {
      toast.error("Failed to lock screen");
    }
  };

  return (
    <AuthLayout
      title="Screen Locked"
      description="Enter your password to unlock your account"
    >
      <div className="space-y-6">
        <CountdownProgress
          duration={30}
          currentTime={countdown}
          className="w-full mb-4"
        />

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <Input
            type="password"
            placeholder="Enter password"
            {...register("password")}
            disabled={countdown === 0}
            className={errors.password ? "border-red-500" : ""}
          />
          {errors.password && (
            <p className="text-red-500 text-sm">{errors.password.message}</p>
          )}

          <div className="flex space-x-4">
            <Button type="submit" className="w-full" disabled={countdown === 0}>
              Unlock
            </Button>
            <Button
              type="button"
              variant="outline"
              className="w-full"
              onClick={handleLock}
            >
              Lock Again
            </Button>
          </div>
        </form>

        {countdown === 0 && (
          <div className="text-center text-red-500 mt-4">
            Unlock time expired. Please lock again.
          </div>
        )}
      </div>
    </AuthLayout>
  );
};

export default LockscreenPage;
