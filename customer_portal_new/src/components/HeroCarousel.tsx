"use client";

import React, { useState, useEffect } from "react";
import { motion, AnimatePresence } from "framer-motion";
import Link from "next/link";
import { ChevronLeft, ChevronRight } from "lucide-react";

interface Banner {
  id: string;
  title: string;
  subtitle: string;
  image: string;
  bgColor: string;
  textColor: string;
  link: string;
  badge?: string;
}

interface HeroCarouselProps {
  banners: Banner[];
}

export default function HeroCarousel({ banners }: HeroCarouselProps) {
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isHovered, setIsHovered] = useState(false);

  useEffect(() => {
    if (isHovered) return;
    const timer = setInterval(() => {
      setCurrentIndex((prev) => (prev === banners.length - 1 ? 0 : prev + 1));
    }, 4000);
    return () => clearInterval(timer);
  }, [isHovered, banners.length]);

  const goToNext = () => setCurrentIndex((prev) => (prev === banners.length - 1 ? 0 : prev + 1));
  const goToPrev = () => setCurrentIndex((prev) => (prev === 0 ? banners.length - 1 : prev - 1));

  if (!banners.length) return null;

  return (
    <div 
      className="relative w-full rounded-2xl overflow-hidden shadow-sm group"
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      <div className="relative h-[250px] md:h-[300px] lg:h-[350px] w-full bg-muted overflow-hidden">
        <AnimatePresence mode="wait">
          <motion.div
            key={currentIndex}
            initial={{ opacity: 0, x: 50 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -50 }}
            transition={{ duration: 0.4, ease: "easeInOut" }}
            className="absolute inset-0 w-full h-full"
          >
            <Link href={banners[currentIndex].link} className="block w-full h-full">
              <div 
                className={`w-full h-full ${banners[currentIndex].bgColor} p-6 lg:p-12 flex flex-col md:flex-row items-center justify-between gap-6 relative`}
              >
                {/* Text Content */}
                <div className={`z-10 relative space-y-3 max-w-xl ${banners[currentIndex].textColor}`}>
                  {banners[currentIndex].badge && (
                    <span className="inline-block bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-2">
                      {banners[currentIndex].badge}
                    </span>
                  )}
                  <h2 className="text-3xl md:text-5xl lg:text-6xl font-black leading-tight tracking-tight drop-shadow-sm">
                    {banners[currentIndex].title}
                  </h2>
                  <p className="font-medium text-lg max-w-md opacity-90 drop-shadow-sm">
                    {banners[currentIndex].subtitle}
                  </p>
                  <button className="bg-white text-gray-900 px-6 py-2.5 rounded-lg font-bold hover:shadow-lg transition-all mt-4">
                    Shop Now
                  </button>
                </div>
                
                {/* Image */}
                <div className="z-10 relative h-full w-1/2 hidden md:flex items-center justify-end pr-4 lg:pr-12">
                   <img 
                     src={banners[currentIndex].image} 
                     alt={banners[currentIndex].title}
                     className="max-h-[90%] object-contain drop-shadow-2xl"
                   />
                </div>

                {/* Decorative Background */}
                <div className="absolute top-0 right-0 w-[600px] h-[600px] bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
              </div>
            </Link>
          </motion.div>
        </AnimatePresence>
      </div>

      {/* Navigation Controls */}
      <button 
        onClick={goToPrev}
        className="absolute left-4 top-1/2 -translate-y-1/2 bg-white/30 backdrop-blur-md hover:bg-white/60 p-2 rounded-full text-white opacity-0 group-hover:opacity-100 transition-opacity"
      >
        <ChevronLeft className="w-6 h-6 text-black" />
      </button>
      <button 
        onClick={goToNext}
        className="absolute right-4 top-1/2 -translate-y-1/2 bg-white/30 backdrop-blur-md hover:bg-white/60 p-2 rounded-full text-white opacity-0 group-hover:opacity-100 transition-opacity"
      >
        <ChevronRight className="w-6 h-6 text-black" />
      </button>

      {/* Indicators */}
      <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2">
        {banners.map((_, i) => (
          <button
            key={i}
            onClick={() => setCurrentIndex(i)}
            className={`transition-all rounded-full ${
              i === currentIndex ? "w-8 h-2 bg-white" : "w-2 h-2 bg-white/50"
            }`}
          />
        ))}
      </div>
    </div>
  );
}
