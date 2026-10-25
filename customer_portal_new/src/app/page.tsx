import React from "react";
import Link from "next/link";
import { Clock, FileText, Pill, Dog, Baby, Heart, Tag } from "lucide-react";
import prisma from "@/lib/prisma";
import HeroCarousel from "@/components/HeroCarousel";

// Placeholder banners representing brands
const HERO_BANNERS = [
  {
    id: "1",
    title: "Stock up on daily essentials",
    subtitle: "Get farm-fresh goodness & a range of exotic fruits, vegetables, eggs & more",
    image: "https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&q=80&w=600",
    bgColor: "bg-gradient-to-r from-green-700 to-green-600",
    textColor: "text-white",
    link: "/store",
    badge: "Supermarket"
  },
  {
    id: "2",
    title: "Aashirvaad Select Atta",
    subtitle: "100% MP Sharbati Wheat. Soft rotis guaranteed.",
    image: "https://images.unsplash.com/photo-1627485937980-221c88ac04f9?auto=format&fit=crop&q=80&w=600",
    bgColor: "bg-gradient-to-r from-amber-700 to-amber-500",
    textColor: "text-white",
    link: "/category/1", // Mapping to foodgrains
    badge: "Top Brand"
  },
  {
    id: "3",
    title: "Cadbury Dairy Milk",
    subtitle: "Share the joy with family packs & celebrations.",
    image: "https://images.unsplash.com/photo-1548883354-94bcfe321cbb?auto=format&fit=crop&q=80&w=600",
    bgColor: "bg-gradient-to-r from-purple-800 to-purple-600",
    textColor: "text-white",
    link: "/category/4", // Mapping to confectionery
    badge: "Special Offer"
  },
  {
    id: "4",
    title: "Fresh Dairy & Milk",
    subtitle: "Farm fresh milk delivered in 10 minutes every morning.",
    image: "https://images.unsplash.com/photo-1550583724-b2692b85b150?auto=format&fit=crop&q=80&w=600",
    bgColor: "bg-gradient-to-r from-blue-600 to-blue-400",
    textColor: "text-white",
    link: "/category/12",
    badge: "Daily Needs"
  }
];

export default async function Home() {
  // Fetch top level categories
  const categories = await prisma.categories.findMany({
    where: { parent_id: 0, status: true },
    take: 16,
    orderBy: { display_order: 'asc' }
  });

  // Fetch Scheme/Offer Items (mrp > selling_price)
  const offers = await prisma.products.findMany({
    where: { 
      status: true,
      mrp: { gt: prisma.products.fields.selling_price } 
    },
    take: 6,
    orderBy: { id: 'desc' }
  });

  // Helper to map category to a real premium image
  const getImageForCategory = (name: string) => {
    const n = name.toLowerCase();
    if (n.includes('foodgrains') || n.includes('masala')) return 'https://images.unsplash.com/photo-1596040033229-a9821ebd058d?auto=format&fit=crop&q=80&w=300';
    if (n.includes('personal') || n.includes('care')) return 'https://images.unsplash.com/photo-1629198688000-71f23e745b6e?auto=format&fit=crop&q=80&w=300';
    if (n.includes('confectionery') || n.includes('sweet')) return 'https://images.unsplash.com/photo-1621939514649-280e2ee25f60?auto=format&fit=crop&q=80&w=300';
    if (n.includes('cleaning')) return 'https://images.unsplash.com/photo-1584820927498-cafe5c152a18?auto=format&fit=crop&q=80&w=300';
    if (n.includes('beverages') || n.includes('drinks')) return 'https://images.unsplash.com/photo-1581006852262-e4307cf6283a?auto=format&fit=crop&q=80&w=300';
    if (n.includes('snacks')) return 'https://images.unsplash.com/photo-1600857544200-b2f666a9a2ec?auto=format&fit=crop&q=80&w=300';
    if (n.includes('dairy')) return 'https://images.unsplash.com/photo-1628088062854-d1870b4553da?auto=format&fit=crop&q=80&w=300';
    if (n.includes('frozen')) return 'https://images.unsplash.com/photo-1588195538326-c5b1e9f80a1b?auto=format&fit=crop&q=80&w=300';
    if (n.includes('baby')) return 'https://images.unsplash.com/photo-1519689680058-324335c77eba?auto=format&fit=crop&q=80&w=300';
    if (n.includes('health')) return 'https://images.unsplash.com/photo-1584308666744-24d5e4a8c3d3?auto=format&fit=crop&q=80&w=300';
    // Generic grocery fallback
    return 'https://images.unsplash.com/photo-1583258292688-d0213dc5a3a8?auto=format&fit=crop&q=80&w=300';
  };

  return (
    <div className="bg-muted/30 min-h-screen pb-12">
      {/* Hero Banner Section (Carousel) */}
      <section className="container mx-auto px-4 lg:px-8 py-6">
        <HeroCarousel banners={HERO_BANNERS} />
      </section>

      {/* Promotional Banners Row */}
      <section className="container mx-auto px-4 lg:px-8 pb-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link href="/category/print" className="bg-[#1C4ED8] rounded-2xl p-5 text-white flex flex-col justify-between hover:-translate-y-1 transition-transform relative overflow-hidden h-40">
            <div className="relative z-10">
              <h3 className="text-lg font-black leading-tight mb-1">Get printouts<br/>delivered</h3>
              <p className="text-xs text-white/80">Safe & secure<br/>Convenient & fast</p>
            </div>
            <button className="bg-white/20 hover:bg-white/30 text-xs font-bold px-3 py-1.5 rounded-lg w-fit mt-2 z-10">Order Now</button>
            <FileText className="absolute -bottom-4 -right-2 w-24 h-24 text-white/20 rotate-12" />
          </Link>
          <Link href="/category/pharmacy" className="bg-[#0D9488] rounded-2xl p-5 text-white flex flex-col justify-between hover:-translate-y-1 transition-transform relative overflow-hidden h-40">
            <div className="relative z-10">
              <h3 className="text-lg font-black leading-tight mb-1">Pharmacy at<br/>your doorstep!</h3>
              <p className="text-xs text-white/80">Cough syrups, pain<br/>relief sprays & more</p>
            </div>
            <button className="bg-white/20 hover:bg-white/30 text-xs font-bold px-3 py-1.5 rounded-lg w-fit mt-2 z-10">Order Now</button>
            <Pill className="absolute -bottom-4 -right-2 w-24 h-24 text-white/20 -rotate-12" />
          </Link>
          <Link href="/category/pets" className="bg-[#EAB308] rounded-2xl p-5 text-gray-900 flex flex-col justify-between hover:-translate-y-1 transition-transform relative overflow-hidden h-40">
            <div className="relative z-10">
              <h3 className="text-lg font-black leading-tight mb-1">Pet care supplies<br/>at your door</h3>
              <p className="text-xs text-gray-800/80">Food, treats, toys & more</p>
            </div>
            <button className="bg-black/80 hover:bg-black text-white text-xs font-bold px-3 py-1.5 rounded-lg w-fit mt-2 z-10">Order Now</button>
            <Dog className="absolute -bottom-4 -right-2 w-24 h-24 text-black/10 rotate-12" />
          </Link>
          <Link href="/category/baby" className="bg-[#94A3B8] rounded-2xl p-5 text-gray-900 flex flex-col justify-between hover:-translate-y-1 transition-transform relative overflow-hidden h-40">
            <div className="relative z-10">
              <h3 className="text-lg font-black leading-tight mb-1">No time for<br/>a diaper run?</h3>
              <p className="text-xs text-gray-800/80">Get baby care essentials</p>
            </div>
            <button className="bg-black/80 hover:bg-black text-white text-xs font-bold px-3 py-1.5 rounded-lg w-fit mt-2 z-10">Order Now</button>
            <Baby className="absolute -bottom-4 -right-2 w-24 h-24 text-black/10 -rotate-12" />
          </Link>
        </div>
      </section>

      {/* Dynamic Categories Grid (With Real Images) */}
      <section className="container mx-auto px-4 lg:px-8 py-4">
        <h2 className="text-xl md:text-2xl font-black text-foreground mb-6">Explore Categories</h2>
        <div className="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-x-4 gap-y-8">
          {categories.map((cat) => (
            <Link
              key={cat.id}
              href={`/category/${cat.id}`}
              className="group flex flex-col items-center justify-start gap-3 text-center cursor-pointer"
            >
              <div className="w-full aspect-square bg-[#F8FAFC] rounded-2xl shadow-sm overflow-hidden flex flex-col items-center justify-center transition-transform group-hover:-translate-y-1 border border-transparent group-hover:border-black/5">
                <img 
                  src={getImageForCategory(cat.name)} 
                  alt={cat.name} 
                  className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                />
              </div>
              <span className="font-semibold text-xs text-foreground leading-tight line-clamp-2 px-1">
                {cat.name}
              </span>
            </Link>
          ))}
        </div>
      </section>

      {/* Offers & Schemes Section */}
      {offers.length > 0 && (
        <section className="container mx-auto px-4 lg:px-8 py-10 mt-6 bg-gradient-to-b from-transparent to-red-50/50">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-2xl font-black text-red-600 flex items-center gap-2">
              <Tag className="w-6 h-6 text-red-600" /> Hot Offers & Schemes
            </h2>
            <Link href="/offers" className="text-red-600 font-bold hover:underline">See All</Link>
          </div>
          
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            {offers.map((item) => {
              const mrp = Number(item.mrp || 0);
              const sp = Number(item.selling_price || 0);
              const discountPct = Math.round(((mrp - sp) / mrp) * 100);
              const imgSrc = item.image1 || `https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&q=80&w=300`;

              return (
                <div key={item.id} className="bg-white rounded-2xl p-3 border border-red-100 shadow-sm hover:shadow-lg transition-all group relative flex flex-col h-full">
                  <div className="absolute top-2 left-2 z-10 bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-sm uppercase tracking-wide">
                    {discountPct}% OFF
                  </div>
                  <button className="absolute top-2 right-2 z-10 p-1.5 text-muted-foreground hover:text-red-600 transition-colors bg-white/80 backdrop-blur rounded-full">
                    <Heart className="w-4 h-4" />
                  </button>
                  <div className="aspect-square bg-muted rounded-xl mb-3 overflow-hidden relative flex items-center justify-center p-2">
                    <img 
                      src={imgSrc} 
                      alt={item.name} 
                      className="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-500 mix-blend-multiply" 
                    />
                  </div>
                  <div className="flex items-center gap-1 mb-1">
                    <Clock className="w-3 h-3 text-red-500" />
                    <span className="text-[10px] font-bold text-red-500 uppercase">10 MINS</span>
                  </div>
                  <h3 className="font-semibold text-sm text-foreground leading-snug line-clamp-2 mb-2 min-h-[2.5rem]">
                    {item.name}
                  </h3>
                  <div className="text-xs text-muted-foreground mb-3 font-medium">
                    {item.carton_size || 1} {item.unit || 'PCS'}
                  </div>
                  <div className="flex items-center justify-between mt-auto pt-2 border-t border-border/50">
                    <div className="flex flex-col">
                      <span className="font-black text-foreground">₹{sp.toFixed(2)}</span>
                      <span className="text-xs text-muted-foreground line-through">₹{mrp.toFixed(2)}</span>
                    </div>
                    <button className="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200 h-9 w-14 rounded-lg font-bold flex items-center justify-center transition-colors">
                      ADD
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </section>
      )}
    </div>
  );
}
