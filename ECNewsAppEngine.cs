// ECNEWSAPP - Unified AI Blogging & Analytics Platform (Powered by .NET 8, C#)
// ------------------------------------------------------------------------------------
// This file integrates: Google Analytics, Matomo, Cookie Consent, Blog AI Template Generator,
// User Profiles, Admin Panel, Rewards System, and Secure AI Tool Command Engine
// ------------------------------------------------------------------------------------

using Microsoft.AspNetCore.Builder;
using Microsoft.AspNetCore.Hosting;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using System;

var builder = WebApplication.CreateBuilder(args);

// Services
builder.Services.AddRazorPages();
builder.Services.AddServerSideBlazor();
builder.Services.AddControllers();
builder.Services.AddSingleton<AITemplateService>(); // AI Blog Template Generator

var app = builder.Build();

if (!app.Environment.IsDevelopment())
{
    app.UseExceptionHandler("/Error");
    app.UseHsts();
}

app.UseHttpsRedirection();
app.UseStaticFiles();
app.UseRouting();

app.MapControllers();
app.MapBlazorHub();
app.MapFallbackToPage("/_Host");

// ------------------------- Google Analytics & Matomo -------------------------
app.Use(async (context, next) => {
    context.Response.Headers.Add("Content-Security-Policy", "script-src 'self' https://www.googletagmanager.com https://matomo.example.com;");
    await next.Invoke();
});

// -------------------------- Cookie Consent Popup ----------------------------
// This would go in your _Layout.cshtml or index.html head section in UI layer
// <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js"></script>
// <script>
// window.addEventListener("load", function(){
//   window.cookieconsent.initialise({
//     palette: { popup: { background: "#000" }, button: { background: "#f1d600" } },
//     theme: "classic",
//     content: { message: "This site uses cookies.", dismiss: "Got it!", link: "Learn more" }
//   })
// });
// </script>

// ---------------------- AI Command Engine for Templates ---------------------
app.MapPost("/api/ai-template", (AITemplateRequest req, AITemplateService service) =>
{
    var result = service.GenerateTemplate(req);
    return Results.Ok(result);
});

// --------------------- Admin Panel (Simplified Auth) ------------------------
app.MapGet("/admin", () => "Welcome to EC News Admin Panel - Secure Access Only");

// ------------------ User System & Rewards (Skeleton Setup) ------------------
app.MapPost("/api/user/login", (UserLogin login) => Results.Ok("User logged in"));
app.MapPost("/api/user/profile", (UserProfile profile) => Results.Ok("Profile saved"));
app.MapPost("/api/rewards/track", (RewardLog log) => Results.Ok("Reward logged"));

app.Run();

// --------------------------- Models and Services ----------------------------
public class AITemplateRequest {
    public string Topic { get; set; }
    public string Language { get; set; }
    public string Keywords { get; set; }
}

public class UserLogin {
    public string Email { get; set; }
    public string Password { get; set; }
}

public class UserProfile {
    public string Name { get; set; }
    public string Bio { get; set; }
    public string Country { get; set; }
}

public class RewardLog {
    public string UserId { get; set; }
    public string Action { get; set; }
    public int Points { get; set; }
}

public class AITemplateService {
    public string GenerateTemplate(AITemplateRequest req) {
        return $"<h2>{req.Topic}</h2><p>This article explores the topic of {req.Topic} in {req.Language}. Focus Keywords: {req.Keywords}</p>";
    }
}
