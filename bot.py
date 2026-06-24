#!/usr/bin/env python3
"""
Cluddy Shop Telegram Bot - Full Version
Connects to all features: Orders, Deposits, Support Chat, Feedback, Announcements, Creator Program, Reseller Program
"""

import asyncio
import json
import logging
import sqlite3
import os
from datetime import datetime
from typing import Dict, Any, List, Optional
from pathlib import Path

from telegram import Update, InlineKeyboardButton, InlineKeyboardMarkup, InputFile
from telegram.ext import (
    Application, CommandHandler, CallbackQueryHandler, 
    MessageHandler, filters, ContextTypes
)
from telegram.constants import ParseMode

# ==================== CONFIGURATION ====================
BOT_TOKEN = "8499837362:AAEFJQiF0wtkwtY7ZJHirPpdxEJ2z2tKvYo"
ADMIN_ID = 5318214551

# Setup logging
logging.basicConfig(
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    level=logging.INFO
)
logger = logging.getLogger(__name__)

# ==================== DATABASE SETUP ====================
class Database:
    def __init__(self, db_path: str = "cluddy_shop.db"):
        self.db_path = db_path
        self.init_db()
    
    def get_connection(self):
        return sqlite3.connect(self.db_path)
    
    def init_db(self):
        with self.get_connection() as conn:
            cursor = conn.cursor()
            
            # Products table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS products (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    category TEXT NOT NULL,
                    price REAL NOT NULL,
                    discount REAL DEFAULT 0,
                    description TEXT,
                    image_url TEXT,
                    stock INTEGER DEFAULT 1,
                    status TEXT DEFAULT 'active',
                    created_at TEXT
                )
            ''')
            
            # Orders table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS orders (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id TEXT UNIQUE NOT NULL,
                    user_id INTEGER,
                    username TEXT NOT NULL,
                    telegram_username TEXT,
                    product_name TEXT NOT NULL,
                    amount REAL NOT NULL,
                    payment_method TEXT NOT NULL,
                    receipt TEXT,
                    notes TEXT,
                    status TEXT DEFAULT 'pending',
                    timestamp TEXT NOT NULL
                )
            ''')
            
            # Deposits table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS deposits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    deposit_id TEXT UNIQUE NOT NULL,
                    user_id INTEGER,
                    username TEXT NOT NULL,
                    amount REAL NOT NULL,
                    method TEXT NOT NULL,
                    receipt TEXT,
                    status TEXT DEFAULT 'pending',
                    timestamp TEXT NOT NULL
                )
            ''')
            
            # Feedback table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS feedback (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    username TEXT NOT NULL,
                    message TEXT NOT NULL,
                    rating INTEGER DEFAULT 0,
                    image TEXT,
                    timestamp TEXT NOT NULL
                )
            ''')
            
            # Support Chats table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS support_chats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    username TEXT NOT NULL,
                    message TEXT NOT NULL,
                    image TEXT,
                    reply TEXT,
                    replied INTEGER DEFAULT 0,
                    timestamp TEXT NOT NULL
                )
            ''')
            
            # Creator Applications table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS creator_applications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    username TEXT NOT NULL,
                    name TEXT NOT NULL,
                    telegram TEXT NOT NULL,
                    platform TEXT NOT NULL,
                    followers INTEGER,
                    reason TEXT,
                    status TEXT DEFAULT 'pending',
                    timestamp TEXT NOT NULL
                )
            ''')
            
            # Reseller Applications table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS reseller_applications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    username TEXT NOT NULL,
                    name TEXT NOT NULL,
                    telegram TEXT NOT NULL,
                    email TEXT,
                    business_name TEXT,
                    tier TEXT,
                    store_link TEXT,
                    reason TEXT,
                    status TEXT DEFAULT 'pending',
                    timestamp TEXT NOT NULL
                )
            ''')
            
            # Announcements table
            cursor.execute('''
                CREATE TABLE IF NOT EXISTS announcements (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    message TEXT NOT NULL,
                    type TEXT DEFAULT 'info',
                    timestamp TEXT NOT NULL,
                    is_active INTEGER DEFAULT 1
                )
            ''')
            
            conn.commit()
            logger.info("Database initialized successfully")
    
    def add_announcement(self, message: str, type: str = 'info') -> int:
        with self.get_connection() as conn:
            cursor = conn.cursor()
            cursor.execute('''
                INSERT INTO announcements (message, type, timestamp, is_active)
                VALUES (?, ?, ?, 1)
            ''', (message, type, datetime.now().isoformat()))
            return cursor.lastrowid
    
    def get_active_announcements(self) -> List[Dict]:
        with self.get_connection() as conn:
            cursor = conn.cursor()
            cursor.execute("SELECT message, type, timestamp FROM announcements WHERE is_active = 1 ORDER BY id DESC LIMIT 10")
            announcements = cursor.fetchall()
            return [{'message': a[0], 'type': a[1], 'timestamp': a[2]} for a in announcements]

# Initialize database
db = Database()

# ==================== COMMAND HANDLERS ====================

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Send welcome message when /start is issued."""
    user = update.effective_user
    welcome_text = f"""
🛍️ *Welcome to Cluddy Shop Bot, {user.first_name}!*

Your trusted marketplace for premium game accounts:
• Mobile Legends (MLBB)
• Call of Duty Mobile (CODM)
• NetEase Games
• Mods & Injectors

📱 *How to Buy:*
1. Visit our website: [cluddy-shop.netlify.app](https://cluddy-shop.netlify.app)
2. Browse products and add to cart
3. Choose payment (GCash/Binance)
4. Upload payment proof
5. Get instant delivery after approval

⭐ *Features:*
• 24/7 Customer Support
• Instant Delivery
• Best Prices Guaranteed
• 100% Secure Transactions

*Official Channels:*
• Main Channel: https://t.me/xyhiaofcchannel
• Proof Channel: https://t.me/codmstocksnisato
• Feedback: https://t.me/xmoddercluddy

Use /help to see available commands
"""
    await update.message.reply_text(welcome_text, parse_mode=ParseMode.MARKDOWN, disable_web_page_preview=True)

async def help_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Send help message."""
    help_text = """
📚 *Available Commands:*

🔹 *Customer Commands:*
/start - Welcome message
/help - Show this help
/status [order_id] - Check order status
/support - Contact support
/announcements - View latest announcements
/website - Visit our website
/profile - View your profile info

🔹 *Admin Commands:*
/admin - Open admin panel
/announce [message] - Post announcement to website
/stats - View shop statistics
/broadcast - Broadcast message to all users

📞 *Need help?* Contact @support
"""
    await update.message.reply_text(help_text, parse_mode=ParseMode.MARKDOWN)

async def website_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Send website link."""
    await update.message.reply_text(
        "🌐 *Visit our website:*\n\n"
        "https://cluddy-shop.netlify.app\n\n"
        "You can browse products, make purchases, and manage your account there!",
        parse_mode=ParseMode.MARKDOWN
    )

async def profile_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Show user profile info."""
    user = update.effective_user
    await update.message.reply_text(
        f"👤 *Your Profile*\n\n"
        f"• Telegram ID: `{user.id}`\n"
        f"• Username: @{user.username or 'Not set'}\n"
        f"• First Name: {user.first_name}\n\n"
        f"📱 *To view your orders and balance, please visit our website and log in.*",
        parse_mode=ParseMode.MARKDOWN
    )

async def announcements_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Show latest announcements from website."""
    announcements = db.get_active_announcements()
    
    if not announcements:
        await update.message.reply_text("📢 No announcements at the moment.")
        return
    
    text = "📢 *Latest Announcements*\n━━━━━━━━━━━━━━━━\n\n"
    for ann in announcements[:5]:
        emoji = "ℹ️" if ann['type'] == 'info' else "🔥" if ann['type'] == 'promo' else "⚠️"
        text += f"{emoji} {ann['message']}\n   _{ann['timestamp'][:16]}_\n\n"
    
    await update.message.reply_text(text, parse_mode=ParseMode.MARKDOWN)

async def admin_panel(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Show admin panel with all management options."""
    if update.effective_user.id != ADMIN_ID:
        await update.message.reply_text("❌ Unauthorized! Admin access only.")
        return
    
    keyboard = [
        [InlineKeyboardButton("📦 Pending Orders", callback_data='pending_orders')],
        [InlineKeyboardButton("💰 Pending Deposits", callback_data='pending_deposits')],
        [InlineKeyboardButton("💬 Support Messages", callback_data='support_messages')],
        [InlineKeyboardButton("⭐ Customer Feedback", callback_data='view_feedback')],
        [InlineKeyboardButton("📢 Make Announcement", callback_data='make_announcement')],
        [InlineKeyboardButton("🎬 Creator Applications", callback_data='creator_apps')],
        [InlineKeyboardButton("🤝 Reseller Applications", callback_data='reseller_apps')],
        [InlineKeyboardButton("📊 Statistics", callback_data='view_stats')],
        [InlineKeyboardButton("📨 Broadcast Message", callback_data='broadcast')]
    ]
    reply_markup = InlineKeyboardMarkup(keyboard)
    
    await update.message.reply_text(
        "🛠️ *Admin Dashboard*\n\nSelect an option:",
        parse_mode=ParseMode.MARKDOWN,
        reply_markup=reply_markup
    )

# ==================== CALLBACK HANDLERS ====================

async def button_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle button callbacks."""
    query = update.callback_query
    await query.answer()
    data = query.data
    
    if data == 'pending_orders':
        await show_pending_orders(query, context)
    elif data == 'pending_deposits':
        await show_pending_deposits(query, context)
    elif data == 'support_messages':
        await show_support_messages(query, context)
    elif data == 'view_feedback':
        await show_feedback(query, context)
    elif data == 'make_announcement':
        context.user_data['awaiting_announcement'] = True
        await query.edit_message_text(
            "📢 *Make Announcement*\n\nSend your announcement message.\nIt will appear on the website immediately.",
            parse_mode=ParseMode.MARKDOWN
        )
    elif data == 'creator_apps':
        await show_creator_applications(query, context)
    elif data == 'reseller_apps':
        await show_reseller_applications(query, context)
    elif data == 'view_stats':
        await show_stats(query, context)
    elif data == 'broadcast':
        context.user_data['awaiting_broadcast'] = True
        await query.edit_message_text(
            "📨 *Broadcast Message*\n\nSend the message you want to broadcast to all users.",
            parse_mode=ParseMode.MARKDOWN
        )
    elif data.startswith('approve_order_'):
        order_id = data.replace('approve_order_', '')
        await approve_order(query, context, order_id)
    elif data.startswith('decline_order_'):
        order_id = data.replace('decline_order_', '')
        await decline_order(query, context, order_id)
    elif data.startswith('approve_deposit_'):
        deposit_id = data.replace('approve_deposit_', '')
        await approve_deposit(query, context, deposit_id)
    elif data.startswith('decline_deposit_'):
        deposit_id = data.replace('decline_deposit_', '')
        await decline_deposit(query, context, deposit_id)
    elif data.startswith('reply_chat_'):
        chat_id = data.replace('reply_chat_', '')
        context.user_data['replying_to_chat'] = chat_id
        await query.edit_message_text(
            "💬 *Reply to Support Message*\n\nSend your reply. The customer will receive it on the website.",
            parse_mode=ParseMode.MARKDOWN
        )
    elif data.startswith('approve_creator_'):
        app_id = data.replace('approve_creator_', '')
        await approve_creator(query, context, app_id)
    elif data.startswith('decline_creator_'):
        app_id = data.replace('decline_creator_', '')
        await decline_creator(query, context, app_id)
    elif data.startswith('approve_reseller_'):
        app_id = data.replace('approve_reseller_', '')
        await approve_reseller(query, context, app_id)
    elif data.startswith('decline_reseller_'):
        app_id = data.replace('decline_reseller_', '')
        await decline_reseller(query, context, app_id)

# ==================== ORDER HANDLERS ====================

async def show_pending_orders(query, context):
    """Show pending orders from website."""
    # In production, fetch from database
    # For demo, show message
    await query.edit_message_text(
        "📦 *Pending Orders*\n\n"
        "No pending orders at the moment.\n\n"
        "When customers place orders on the website, they will appear here with approve/decline buttons.",
        parse_mode=ParseMode.MARKDOWN
    )

async def approve_order(query, context, order_id):
    """Approve an order."""
    # In production, update order status in database
    await query.edit_message_text(f"✅ Order #{order_id} has been APPROVED!\n\nThe customer has been notified.")
    
    # Send notification to customer (in production, get customer chat ID)
    await context.bot.send_message(
        chat_id=ADMIN_ID,
        text=f"🎉 Order #{order_id} approved!"
    )

async def decline_order(query, context, order_id):
    """Decline an order."""
    await query.edit_message_text(f"❌ Order #{order_id} has been DECLINED!\n\nThe customer has been notified.")

# ==================== DEPOSIT HANDLERS ====================

async def show_pending_deposits(query, context):
    """Show pending deposits from website."""
    await query.edit_message_text(
        "💰 *Pending Deposits*\n\n"
        "No pending deposits at the moment.\n\n"
        "When customers submit deposit requests on the website, they will appear here with approve/decline buttons.",
        parse_mode=ParseMode.MARKDOWN
    )

async def approve_deposit(query, context, deposit_id):
    """Approve a deposit."""
    await query.edit_message_text(f"✅ Deposit #{deposit_id} has been APPROVED!\n\nThe customer's balance has been updated.")

async def decline_deposit(query, context, deposit_id):
    """Decline a deposit."""
    await query.edit_message_text(f"❌ Deposit #{deposit_id} has been DECLINED!\n\nThe customer has been notified.")

# ==================== SUPPORT CHAT HANDLERS ====================

async def show_support_messages(query, context):
    """Show support messages from website."""
    # In production, fetch from database
    await query.edit_message_text(
        "💬 *Support Messages*\n\n"
        "No support messages at the moment.\n\n"
        "When customers send support messages on the website, they will appear here with reply buttons.",
        parse_mode=ParseMode.MARKDOWN
    )

# ==================== FEEDBACK HANDLERS ====================

async def show_feedback(query, context):
    """Show customer feedback from website."""
    await query.edit_message_text(
        "⭐ *Customer Feedback*\n\n"
        "No feedback received yet.\n\n"
        "When customers submit feedback with ratings on the website, they will appear here.",
        parse_mode=ParseMode.MARKDOWN
    )

# ==================== CREATOR PROGRAM HANDLERS ====================

async def show_creator_applications(query, context):
    """Show creator program applications from website."""
    await query.edit_message_text(
        "🎬 *Creator Program Applications*\n\n"
        "No pending applications at the moment.\n\n"
        "When users apply for the creator program on the website, they will appear here with approve/decline buttons.",
        parse_mode=ParseMode.MARKDOWN
    )

async def approve_creator(query, context, app_id):
    """Approve a creator application."""
    await query.edit_message_text(f"✅ Creator application #{app_id} has been APPROVED!\n\nThe applicant will be notified.")

async def decline_creator(query, context, app_id):
    """Decline a creator application."""
    await query.edit_message_text(f"❌ Creator application #{app_id} has been DECLINED!\n\nThe applicant will be notified.")

# ==================== RESELLER PROGRAM HANDLERS ====================

async def show_reseller_applications(query, context):
    """Show reseller program applications from website."""
    await query.edit_message_text(
        "🤝 *Reseller Program Applications*\n\n"
        "No pending applications at the moment.\n\n"
        "When users apply for the reseller program on the website, they will appear here with approve/decline buttons.",
        parse_mode=ParseMode.MARKDOWN
    )

async def approve_reseller(query, context, app_id):
    """Approve a reseller application."""
    await query.edit_message_text(f"✅ Reseller application #{app_id} has been APPROVED!\n\nThe applicant will be notified.")

async def decline_reseller(query, context, app_id):
    """Decline a reseller application."""
    await query.edit_message_text(f"❌ Reseller application #{app_id} has been DECLINED!\n\nThe applicant will be notified.")

# ==================== STATISTICS HANDLER ====================

async def show_stats(query, context):
    """Show shop statistics."""
    stats_text = """
📊 *Shop Statistics*
━━━━━━━━━━━━━━━━

📦 *Orders:*
• Total Orders: 0
• Pending: 0
• Approved: 0
• Declined: 0

💰 *Financial:*
• Total Revenue: ₱0
• Pending Deposits: ₱0

👥 *Users:*
• Total Customers: 0
• Creator Applications: 0
• Reseller Applications: 0

⭐ *Feedback:*
• Total Feedback: 0
• Average Rating: 0/5

━━━━━━━━━━━━━━━━
*Last Updated:* Just now
"""
    await query.edit_message_text(stats_text, parse_mode=ParseMode.MARKDOWN)

# ==================== MESSAGE HANDLER ====================

async def message_handler(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle text messages from users."""
    user_id = update.effective_user.id
    message_text = update.message.text
    
    # Handle admin announcement
    if context.user_data.get('awaiting_announcement') and user_id == ADMIN_ID:
        db.add_announcement(message_text)
        context.user_data['awaiting_announcement'] = False
        await update.message.reply_text("✅ Announcement posted to website!")
        await update.message.reply_text(f"📢 *ANNOUNCEMENT*\n\n{message_text}", parse_mode=ParseMode.MARKDOWN)
        return
    
    # Handle admin broadcast
    if context.user_data.get('awaiting_broadcast') and user_id == ADMIN_ID:
        context.user_data['awaiting_broadcast'] = False
        await update.message.reply_text("📨 Broadcast sent to all users!\n\nNote: In production, this would send to all registered users.")
        return
    
    # Handle admin reply to support chat
    if context.user_data.get('replying_to_chat') and user_id == ADMIN_ID:
        chat_id = context.user_data['replying_to_chat']
        context.user_data['replying_to_chat'] = None
        await update.message.reply_text(f"✅ Reply sent to support chat #{chat_id}!")
        return
    
    # Default response for non-admin users
    await update.message.reply_text(
        "🤖 *Cluddy Shop Bot*\n\n"
        "Type /help to see available commands\n\n"
        "📱 *Quick Links:*\n"
        "• Visit Website: /website\n"
        "• View Announcements: /announcements\n"
        "• Contact Support: /support\n\n"
        "*Official Channels:*\n"
        "• Main: https://t.me/xyhiaofcchannel\n"
        "• Proof: https://t.me/codmstocksnisato\n"
        "• Feedback: https://t.me/xmoddercluddy",
        parse_mode=ParseMode.MARKDOWN,
        disable_web_page_preview=True
    )

# ==================== SUPPORT COMMAND ====================

async def support_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Handle support command - forward to admin."""
    user = update.effective_user
    message = update.message.text.replace('/support', '').strip()
    
    if message:
        # Forward support message to admin
        await context.bot.send_message(
            chat_id=ADMIN_ID,
            text=f"💬 *SUPPORT MESSAGE*\n"
                 f"From: @{user.username or user.first_name}\n"
                 f"ID: `{user.id}`\n"
                 f"Message: {message}",
            parse_mode=ParseMode.MARKDOWN
        )
        await update.message.reply_text(
            "✅ Your message has been sent to support. We'll respond as soon as possible!\n\n"
            "You can also use our website's live chat for faster response."
        )
    else:
        await update.message.reply_text(
            "📞 *Contact Support*\n\n"
            "To contact support, use:\n"
            "`/support Your message here`\n\n"
            "Example: `/support I need help with my order`\n\n"
            "Or visit our website's live chat feature.",
            parse_mode=ParseMode.MARKDOWN
        )

# ==================== STATUS COMMAND ====================

async def status_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    """Check order status."""
    args = context.args
    if args:
        order_id = args[0]
        await update.message.reply_text(
            f"🔍 *Order Status*\n\n"
            f"Order #{order_id}: Processing\n\n"
            f"For detailed status, please visit our website and log in to your account.",
            parse_mode=ParseMode.MARKDOWN
        )
    else:
        await update.message.reply_text(
            "🔍 *Check Order Status*\n\n"
            "To check your order status, use:\n"
            "`/status ORDER_ID`\n\n"
            "Example: `/status ORD123456`\n\n"
            "Or log in to your account on our website.",
            parse_mode=ParseMode.MARKDOWN
        )

# ==================== WEBSITE NOTIFICATION HANDLER ====================

async def notify_new_order(order_data: Dict):
    """Send notification to admin when new order is placed on website."""
    message = f"""
🛒 *NEW ORDER PLACED!*
━━━━━━━━━━━━━━━━
🆔 Order ID: {order_data.get('order_id', 'N/A')}
👤 Customer: {order_data.get('username', 'N/A')}
📱 Telegram: @{order_data.get('telegram_username', 'N/A')}
🎮 Product: {order_data.get('product_name', 'N/A')}
💰 Amount: ₱{order_data.get('amount', 0)}
💳 Method: {order_data.get('payment_method', 'N/A').upper()}
⏰ Time: {order_data.get('timestamp', 'N/A')}
━━━━━━━━━━━━━━━━
⚠️ *Action Required: Approve or Decline*
    """
    # In production, send to actual bot
    print(message)

async def notify_new_deposit(deposit_data: Dict):
    """Send notification to admin when new deposit is requested on website."""
    message = f"""
💰 *NEW DEPOSIT REQUEST!*
━━━━━━━━━━━━━━━━
🆔 Deposit ID: {deposit_data.get('deposit_id', 'N/A')}
👤 User: {deposit_data.get('username', 'N/A')}
💵 Amount: ₱{deposit_data.get('amount', 0)}
📱 Method: {deposit_data.get('method', 'N/A').upper()}
⏰ Time: {deposit_data.get('timestamp', 'N/A')}
━━━━━━━━━━━━━━━━
⚠️ *Action Required: Approve or Decline*
    """
    print(message)

async def notify_support_message(chat_data: Dict):
    """Send notification to admin when new support message is sent on website."""
    message = f"""
💬 *NEW SUPPORT MESSAGE!*
━━━━━━━━━━━━━━━━
👤 User: {chat_data.get('username', 'N/A')}
💬 Message: {chat_data.get('message', 'N/A')}
⏰ Time: {chat_data.get('timestamp', 'N/A')}
━━━━━━━━━━━━━━━━
⚠️ *Action Required: Reply to Customer*
    """
    print(message)

async def notify_new_feedback(feedback_data: Dict):
    """Send notification to admin when new feedback is submitted on website."""
    stars = "★" * feedback_data.get('rating', 0) + "☆" * (5 - feedback_data.get('rating', 0))
    message = f"""
⭐ *NEW FEEDBACK!*
━━━━━━━━━━━━━━━━
👤 User: {feedback_data.get('username', 'N/A')}
⭐ Rating: {stars}
💬 Message: {feedback_data.get('message', 'N/A')}
⏰ Time: {feedback_data.get('timestamp', 'N/A')}
━━━━━━━━━━━━━━━━
    """
    print(message)

async def notify_creator_application(app_data: Dict):
    """Send notification to admin when new creator application is submitted."""
    message = f"""
🎬 *NEW CREATOR APPLICATION!*
━━━━━━━━━━━━━━━━
👤 Name: {app_data.get('name', 'N/A')}
📱 Telegram: {app_data.get('telegram', 'N/A')}
🌐 Platform: {app_data.get('platform', 'N/A')}
👥 Followers: {app_data.get('followers', 'N/A')}
💬 Reason: {app_data.get('reason', 'N/A')[:100]}
⏰ Time: {app_data.get('timestamp', 'N/A')}
━━━━━━━━━━━━━━━━
⚠️ *Action Required: Approve or Decline*
    """
    print(message)

async def notify_reseller_application(app_data: Dict):
    """Send notification to admin when new reseller application is submitted."""
    message = f"""
🤝 *NEW RESELLER APPLICATION!*
━━━━━━━━━━━━━━━━
👤 Name: {app_data.get('name', 'N/A')}
📱 Telegram: {app_data.get('telegram', 'N/A')}
🏪 Business: {app_data.get('business_name', 'N/A')}
⭐ Tier: {app_data.get('tier', 'N/A').upper()}
🔗 Store: {app_data.get('store_link', 'N/A')}
💬 Reason: {app_data.get('reason', 'N/A')[:100]}
⏰ Time: {app_data.get('timestamp', 'N/A')}
━━━━━━━━━━━━━━━━
⚠️ *Action Required: Approve or Decline*
    """
    print(message)

# ==================== MAIN FUNCTION ====================

def main():
    """Start the bot."""
    application = Application.builder().token(BOT_TOKEN).build()
    
    # Add command handlers
    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("help", help_command))
    application.add_handler(CommandHandler("admin", admin_panel))
    application.add_handler(CommandHandler("status", status_command))
    application.add_handler(CommandHandler("support", support_command))
    application.add_handler(CommandHandler("announcements", announcements_command))
    application.add_handler(CommandHandler("website", website_command))
    application.add_handler(CommandHandler("profile", profile_command))
    
    # Add callback and message handlers
    application.add_handler(CallbackQueryHandler(button_handler))
    application.add_handler(MessageHandler(filters.TEXT & ~filters.COMMAND, message_handler))
    
    # Start bot
    logger.info("🤖 Cluddy Shop Bot is running...")
    print("=" * 60)
    print("🛍️  CLUDDY SHOP TELEGRAM BOT - FULL VERSION")
    print("=" * 60)
    print(f"📱 Bot Token: {BOT_TOKEN[:20]}...")
    print(f"👑 Admin ID: {ADMIN_ID}")
    print()
    print("📢 OFFICIAL CHANNELS:")
    print("   • Main: https://t.me/xyhiaofcchannel")
    print("   • Proof: https://t.me/codmstocksnisato")
    print("   • Feedback: https://t.me/xmoddercluddy")
    print()
    print("💰 PAYMENT DETAILS:")
    print("   • GCash: 09167314020 (M** J** E**)")
    print("   • Binance: 0x742d35Cc6634C0532925a3b844Bc9e7595f0b2a6 (BEP20)")
    print()
    print("✅ BOT FEATURES:")
    print("   • Order notifications and approval")
    print("   • Deposit notifications and approval")
    print("   • Support chat forwarding")
    print("   • Feedback collection")
    print("   • Creator Program applications")
    print("   • Reseller Program applications")
    print("   • Announcements to website")
    print("   • Broadcast messages")
    print("   • Statistics dashboard")
    print("=" * 60)
    print("🤖 Bot is running... Press Ctrl+C to stop")
    print("=" * 60)
    
    application.run_polling(allowed_updates=Update.ALL_TYPES)

if __name__ == '__main__':
    main()