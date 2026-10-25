"use client";

import React, { useState } from "react";
import { Search, MapPin, ShoppingCart, User, Menu, X, ChevronDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

export default function Header() {
  const [isScrolled, setIsScrolled] = useState(false);
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  React.useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 10);
    };
    window.addEventListener("scroll", handleScroll);
    return () => window.removeEventListener("scroll", handleScroll);
  }, []);

  return (
    <>
      <header
        className={`sticky top-0 z-50 w-full transition-all duration-300 ${
          isScrolled ? "bg-background/80 backdrop-blur-md shadow-sm" : "bg-background"
        }`}
      >
        <div className="container mx-auto px-4 lg:px-8">
          <div className="flex items-center justify-between py-3 gap-4 lg:gap-8">
            
            {/* Logo */}
            <div className="flex items-center gap-4">
              <button
                className="lg:hidden text-foreground"
                onClick={() => setIsMobileMenuOpen(true)}
              >
                <Menu className="w-6 h-6" />
              </button>
              <a href="/" className="flex flex-col">
                <span className="text-2xl font-black text-primary tracking-tight leading-none">
                  Nation's
                </span>
                <span className="text-sm font-bold text-accent tracking-widest leading-none mt-1 uppercase">
                  Choice
                </span>
              </a>
            </div>

            {/* Location & Delivery ETA (Desktop) */}
            <div className="hidden lg:flex items-center gap-2 hover:bg-muted p-2 rounded-lg cursor-pointer transition-colors">
              <div className="bg-primary/10 p-2 rounded-full">
                <MapPin className="w-5 h-5 text-primary" />
              </div>
              <div className="flex flex-col">
                <span className="text-xs font-bold text-foreground">
                  Delivery in 10-20 mins
                </span>
                <span className="text-sm text-muted-foreground flex items-center gap-1">
                  New Delhi, Delhi <ChevronDown className="w-4 h-4" />
                </span>
              </div>
            </div>

            {/* Search Bar */}
            <div className="hidden lg:flex flex-1 max-w-2xl relative group">
              <div className="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                <Search className="w-5 h-5 text-muted-foreground group-focus-within:text-primary transition-colors" />
              </div>
              <input
                type="text"
                placeholder="Search for "
                className="w-full bg-muted border border-transparent focus:bg-background focus:border-primary focus:ring-4 focus:ring-primary/20 text-foreground text-sm rounded-xl pl-12 pr-4 py-3.5 transition-all outline-none"
              />
            </div>

            {/* Actions (Login & Cart) */}
            <div className="flex items-center gap-3 lg:gap-5">
              <button className="hidden lg:flex items-center gap-2 text-foreground font-semibold hover:text-primary transition-colors">
                Login
              </button>
              
              <button className="flex items-center gap-2 bg-primary hover:bg-primary/90 text-primary-foreground px-4 py-2.5 rounded-xl font-bold shadow-[0_4px_14px_0_rgba(22,163,74,0.39)] hover:shadow-[0_6px_20px_rgba(22,163,74,0.23)] hover:-translate-y-0.5 transition-all">
                <ShoppingCart className="w-5 h-5" />
                <span className="hidden lg:inline">My Cart</span>
              </button>
            </div>
          </div>

          {/* Mobile Search Bar (Below Header) */}
          <div className="lg:hidden pb-3">
             <div className="relative w-full">
                <div className="absolute inset-y-0 left-3 flex items-center pointer-events-none">
                  <Search className="w-4 h-4 text-muted-foreground" />
                </div>
                <input
                  type="text"
                  placeholder="Search for products..."
                  className="w-full bg-muted border border-transparent focus:bg-background focus:border-primary focus:ring-2 focus:ring-primary/20 text-foreground text-sm rounded-lg pl-10 pr-4 py-3 transition-all outline-none shadow-inner"
                />
              </div>
          </div>
        </div>
      </header>

      {/* Mobile Menu Overlay */}
      <AnimatePresence>
        {isMobileMenuOpen && (
          <>
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsMobileMenuOpen(false)}
              className="fixed inset-0 bg-black/50 z-[60] lg:hidden backdrop-blur-sm"
            />
            <motion.div
              initial={{ x: "-100%" }}
              animate={{ x: 0 }}
              exit={{ x: "-100%" }}
              transition={{ type: "spring", bounce: 0, duration: 0.4 }}
              className="fixed inset-y-0 left-0 w-3/4 max-w-sm bg-background z-[70] shadow-2xl lg:hidden flex flex-col"
            >
              <div className="p-4 border-b border-border flex justify-between items-center bg-primary/5">
                <div className="flex items-center gap-3">
                  <div className="bg-primary p-2 rounded-full">
                    <User className="w-5 h-5 text-primary-foreground" />
                  </div>
                  <div>
                    <p className="font-bold text-foreground">Welcome Guest</p>
                    <p className="text-xs text-primary font-semibold">Login / Sign Up</p>
                  </div>
                </div>
                <button onClick={() => setIsMobileMenuOpen(false)} className="p-2 bg-background rounded-full shadow-sm">
                  <X className="w-5 h-5 text-foreground" />
                </button>
              </div>
              <div className="flex-1 overflow-y-auto p-4 flex flex-col gap-4">
                <div className="bg-muted p-3 rounded-lg flex items-center gap-3">
                  <MapPin className="w-5 h-5 text-muted-foreground" />
                  <div className="flex flex-col">
                    <span className="text-xs font-bold text-foreground">Delivery to</span>
                    <span className="text-sm text-muted-foreground">New Delhi, Delhi</span>
                  </div>
                </div>
                {/* Categories Link Placeholders */}
                {['Vegetables & Fruits', 'Dairy & Breakfast', 'Munchies', 'Cold Drinks & Juices'].map((cat, i) => (
                  <a key={i} href="#" className="py-3 font-semibold text-foreground border-b border-border hover:text-primary transition-colors">
                    {cat}
                  </a>
                ))}
              </div>
            </motion.div>
          </>
        )}
      </AnimatePresence>
    </>
  );
}
