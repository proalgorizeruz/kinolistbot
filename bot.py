import asyncio
from aiogram import Bot, Dispatcher, types, F
from aiogram.filters import Command
from aiogram.types import InlineKeyboardMarkup, InlineKeyboardButton

TOKEN = "8629680753:AAHbUQvhOQAUuaoSZRSZCvuu5Ahd7hpYg3U"
ADMIN_ID = 8588919185  # O'z IDingizni yozing
KANAL = "@kinolistuz"

bot = Bot(token=TOKEN)
dp = Dispatcher()

# Foydalanuvchilarni saqlash uchun oddiy ro'yxat (Renderda restart bo'lsa o'chadi, bazadan foydalanish tavsiya etiladi)
users = set()

async def is_member(user_id):
    try:
        member = await bot.get_chat_member(chat_id=KANAL, user_id=user_id)
        return member.status in ['creator', 'administrator', 'member']
    except:
        return False

@dp.message(Command("start"))
async def start(message: types.Message):
    users.add(message.from_user.id)
    if await is_member(message.from_user.id):
        await message.answer("Salom! Kino kodini yozing.")
    else:
        btn = InlineKeyboardButton(text="Obuna bo'lish", url=f"https://t.me/{KANAL.replace('@', '')}")
        markup = InlineKeyboardMarkup(inline_keyboard=[[btn]])
        await message.answer("Botdan foydalanish uchun kanalimizga obuna bo'ling!", reply_markup=markup)

@dp.message(F.text.isdigit())
async def search_movie(message: types.Message):
    if await is_member(message.from_user.id):
        await message.answer(f"Kino qidirilmoqda: {message.text}...")
    else:
        await message.answer("Kanalga obuna bo'ling!")

# Admin panel soddalashtirilgan holatda
@dp.message(Command("panel"), F.from_user.id == ADMIN_ID)
async def admin_panel(message: types.Message):
    await message.answer(f"Statistika: {len(users)} ta foydalanuvchi")

async def main():
    await dp.start_polling(bot)

if name == "main":
    asyncio.run(main())