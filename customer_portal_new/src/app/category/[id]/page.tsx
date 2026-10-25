import React from "react";
import { notFound } from "next/navigation";
import prisma from "@/lib/prisma";
import { Clock, Heart, ArrowLeft } from "lucide-react";
import Link from "next/link";

interface CategoryPageProps {
  params: Promise<{ id: string }>;
}

export default async function CategoryPage({ params }: CategoryPageProps) {
  const { id } = await params;
  
  // Try parsing as integer for real DB categories
  const categoryId = parseInt(id, 10);
  
  let categoryName = "Special Category";
  let products = [];
  
  if (!isNaN(categoryId)) {
    const category = await prisma.categories.findUnique({
      where: { id: categoryId }
    });
    
    if (!category) {
      return notFound();
    }
    
    categoryName = category.name;
    
    // Fetch products belonging to this category
    // In many schemas, products might map by name, or there's a category_id field.
    // Let's check category_id or category name matching.
    products = await prisma.products.findMany({
      where: {
        OR: [
          { category_id: categoryId },
          { category: category.name }
        ],
        status: true
      },
      take: 24
    });
  } else {
    // Handling promotional string categories (pharmacy, print, pets, baby)
    categoryName = id.charAt(0).toUpperCase() + id.slice(1);
    // Fetch random products for these for demo purposes
    products = await prisma.products.findMany({
      where: { status: true },
      take: 12
    });
  }

  return (
    <div className="bg-muted/30 min-h-screen pb-12">
      
      <div className="bg-white border-b border-border sticky top-[72px] z-30 shadow-sm">
        <div className="container mx-auto px-4 lg:px-8 py-4 flex items-center gap-4">
          <Link href="/" className="p-2 hover:bg-muted rounded-full transition-colors">
            <ArrowLeft className="w-5 h-5 text-foreground" />
          </Link>
          <h1 className="text-xl md:text-2xl font-black text-foreground">{categoryName}</h1>
          <span className="text-sm text-muted-foreground font-medium ml-2">
            ({products.length} products found)
          </span>
        </div>
      </div>

      <div className="container mx-auto px-4 lg:px-8 py-8">
        {products.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-20 text-center">
            <span className="text-6xl mb-4">🛒</span>
            <h2 className="text-2xl font-bold text-foreground">No products found</h2>
            <p className="text-muted-foreground mt-2 max-w-md">
              We couldn't find any items in this category right now. Check back later!
            </p>
            <Link href="/" className="mt-6 bg-primary text-primary-foreground px-6 py-2 rounded-lg font-bold">
              Return Home
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            {products.map((item) => {
              // Calculate discount roughly for display if MRP > Selling Price
              const mrp = Number(item.mrp || 0);
              const sp = Number(item.selling_price || 0);
              let discountPct = 0;
              if (mrp > sp && mrp > 0) {
                discountPct = Math.round(((mrp - sp) / mrp) * 100);
              }
              
              // Fallback image
              const imgSrc = item.image1 || `https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?auto=format&fit=crop&q=80&w=300`;

              return (
                <div key={item.id} className="bg-card rounded-2xl p-3 border border-border shadow-sm hover:shadow-lg transition-all group relative flex flex-col h-full">
                  
                  {discountPct > 0 && (
                    <div className="absolute top-2 left-2 z-10 bg-accent text-white text-[10px] font-black px-2 py-0.5 rounded-sm uppercase tracking-wide">
                      {discountPct}% OFF
                    </div>
                  )}
                  
                  <button className="absolute top-2 right-2 z-10 p-1.5 text-muted-foreground hover:text-primary transition-colors bg-white/80 backdrop-blur rounded-full">
                    <Heart className="w-4 h-4" />
                  </button>
                  
                  <div className="aspect-square bg-white rounded-xl mb-3 overflow-hidden relative flex items-center justify-center p-2">
                    <img 
                      src={imgSrc} 
                      alt={item.name} 
                      className="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-500 mix-blend-multiply" 
                    />
                  </div>
                  
                  <div className="flex items-center gap-1 mb-1">
                    <Clock className="w-3 h-3 text-muted-foreground" />
                    <span className="text-[10px] font-bold text-muted-foreground uppercase">12 MINS</span>
                  </div>
                  
                  <h3 className="font-semibold text-sm text-foreground leading-snug line-clamp-2 mb-2 min-h-[2.5rem]" title={item.name}>
                    {item.name}
                  </h3>
                  
                  <div className="text-xs text-muted-foreground mb-3 font-medium">
                    {item.carton_size || 1} {item.unit || 'PCS'}
                  </div>
                  
                  <div className="flex items-center justify-between mt-auto pt-2 border-t border-border/50">
                    <div className="flex flex-col">
                      <span className="font-black text-foreground">₹{sp.toFixed(2)}</span>
                      {discountPct > 0 && (
                        <span className="text-xs text-muted-foreground line-through">₹{mrp.toFixed(2)}</span>
                      )}
                    </div>
                    <button className="bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground border border-primary/20 h-9 w-14 rounded-lg font-bold flex items-center justify-center transition-colors">
                      ADD
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
