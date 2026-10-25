import React from "react";
import { Link2 } from "lucide-react";

export default function Footer() {
  return (
    <footer className="bg-background border-t border-border pt-16 pb-8 mt-16">
      <div className="container mx-auto px-4 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8 mb-12">
          
          <div className="lg:col-span-2">
            <a href="/" className="flex flex-col mb-4">
              <span className="text-3xl font-black text-primary tracking-tight leading-none">
                Nation's
              </span>
              <span className="text-sm font-bold text-accent tracking-widest leading-none mt-1 uppercase">
                Choice
              </span>
            </a>
            <p className="text-muted-foreground text-sm mb-6 max-w-sm leading-relaxed">
              Nation's Choice is your premium online supermarket, bringing you the freshest 
              fruits, vegetables, and daily essentials in 10-20 minutes. Quality you can trust, 
              delivered straight to your door.
            </p>
            <div className="flex items-center gap-4">
              <a href="#" className="bg-muted p-2 rounded-full text-foreground hover:bg-primary hover:text-primary-foreground transition-colors font-bold text-xs px-3">
                FB
              </a>
              <a href="#" className="bg-muted p-2 rounded-full text-foreground hover:bg-primary hover:text-primary-foreground transition-colors font-bold text-xs px-3">
                TW
              </a>
              <a href="#" className="bg-muted p-2 rounded-full text-foreground hover:bg-primary hover:text-primary-foreground transition-colors font-bold text-xs px-3">
                IG
              </a>
              <a href="#" className="bg-muted p-2 rounded-full text-foreground hover:bg-primary hover:text-primary-foreground transition-colors font-bold text-xs px-3">
                IN
              </a>
            </div>
          </div>

          <div>
            <h3 className="font-bold text-foreground mb-4 text-lg">Categories</h3>
            <ul className="space-y-3">
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Fruits & Vegetables</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Dairy & Bakery</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Snacks & Beverages</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Personal Care</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Home Care</a></li>
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-foreground mb-4 text-lg">Useful Links</h3>
            <ul className="space-y-3">
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">About Us</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Careers</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Blog</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Press</a></li>
              <li><a href="#" className="text-muted-foreground text-sm hover:text-primary transition-colors">Lead</a></li>
            </ul>
          </div>

          <div>
            <h3 className="font-bold text-foreground mb-4 text-lg">Download App</h3>
            <div className="flex flex-col gap-3">
              <a href="#" className="inline-block">
                <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="App Store" className="h-10" />
              </a>
              <a href="#" className="inline-block">
                <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Google Play" className="h-10" />
              </a>
            </div>
          </div>

        </div>

        <div className="border-t border-border pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
          <p className="text-sm text-muted-foreground">
            &copy; {new Date().getFullYear()} Nation's Choice Department Store. All rights reserved.
          </p>
          <div className="flex gap-4">
            <a href="#" className="text-sm text-muted-foreground hover:text-primary transition-colors">Privacy Policy</a>
            <a href="#" className="text-sm text-muted-foreground hover:text-primary transition-colors">Terms of Service</a>
            <a href="#" className="text-sm text-muted-foreground hover:text-primary transition-colors">Return Policy</a>
          </div>
        </div>
      </div>
    </footer>
  );
}
