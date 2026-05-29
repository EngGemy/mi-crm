<x-filament-panels::page>
<div dir="rtl" class="space-y-5" wire:poll.60s="loadData">

    {{-- Stats strip --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @php
            $overdueCount  = count(array_filter($todayTasks, fn($t) => $t['is_overdue']));
            $pendingCount  = count($todayTasks);
            $upcomingCount = count($upcomingMeetings);
            $reminderCount = count($dueReminders);
        @endphp

        <div class="flex items-center gap-4 bg-white dark:bg-gray-900 rounded-xl border {{ $overdueCount > 0 ? 'border-red-200 dark:border-red-800' : 'border-gray-200 dark:border-gray-700' }} p-4 shadow-sm">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $overdueCount > 0 ? 'bg-red-100 dark:bg-red-900/40' : 'bg-gray-100 dark:bg-gray-800' }} shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 {{ $overdueCount > 0 ? 'text-red-500' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold {{ $overdueCount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">{{ $overdueCount }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">مهام متأخرة</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-white dark:bg-gray-900 rounded-xl border border-blue-200 dark:border-blue-800 p-4 shadow-sm">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-blue-100 dark:bg-blue-900/40 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $pendingCount }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">مهام معلّقة</p>
            </div>
        </div>

        <div class="flex items-center gap-4 bg-white dark:bg-gray-900 rounded-xl border border-green-200 dark:border-green-800 p-4 shadow-sm">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-green-100 dark:bg-green-900/40 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $upcomingCount }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">مواعيد الأسبوع</p>
            </div>
        </div>
    </div>

    {{-- Main layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Left column: reminders + tasks table --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Reminders (today + overdue) --}}
            @if($reminderCount > 0)
            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
                <div class="bg-amber-50 dark:bg-amber-950/30 px-4 py-3 flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-amber-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5.85 3.5a.75.75 0 00-1.117-1 9.719 9.719 0 00-2.348 4.876.75.75 0 001.479.248A8.219 8.219 0 015.85 3.5zM19.267 2.5a.75.75 0 10-1.118 1 8.22 8.22 0 011.987 4.124.75.75 0 001.479-.248A9.72 9.72 0 0019.267 2.5zM12 2.25A6.75 6.75 0 005.25 9v.75a8.217 8.217 0 01-2.119 5.52.75.75 0 00.298 1.206c1.544.57 3.16.99 4.831 1.243a3.75 3.75 0 107.48 0 24.583 24.583 0 004.83-1.244.75.75 0 00.298-1.205 8.217 8.217 0 01-2.118-5.52V9A6.75 6.75 0 0012 2.25z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-amber-700 dark:text-amber-300">تذكيرات اليوم ({{ $reminderCount }})</h3>
                </div>
                <div class="divide-y divide-amber-50 dark:divide-gray-800">
                    @foreach($dueReminders as $r)
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-amber-50/30 dark:hover:bg-amber-950/10 transition-colors">
                        <div class="flex items-start gap-3 min-w-0">
                            @if($r['is_overdue'])
                            <span class="mt-0.5 w-2 h-2 rounded-full bg-red-500 shrink-0 animate-pulse"></span>
                            @else
                            <span class="mt-0.5 w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
                            @endif
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-gray-800 dark:text-gray-200">{{ $r['title'] }}</p>
                                @if($r['lead_name'])
                                <p class="text-xs text-gray-400 mt-0.5">{{ $r['lead_name'] }} · {{ $r['type_label'] }}</p>
                                @endif
                                <p class="text-xs font-mono mt-0.5 {{ $r['is_overdue'] ? 'text-red-400' : 'text-amber-500' }}">
                                    {{ $r['remind_at'] }}
                                    @if($r['is_overdue']) <span class="font-sans normal-case"> — متأخر</span>@endif
                                </p>
                            </div>
                        </div>
                        <button
                            wire:click="snoozeReminder({{ $r['id'] }})"
                            class="shrink-0 text-xs font-medium text-orange-600 hover:text-orange-800 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-lg px-3 py-1.5 transition-colors whitespace-nowrap mr-3"
                        >تأجيل ساعة</button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tasks Table --}}
            <div
                class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden shadow-sm"
                x-data="{ showForm: @entangle('showAddTask') }"
            >
                {{-- Table header --}}
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gradient-to-l from-blue-50/50 to-white dark:from-blue-950/10 dark:to-gray-900">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 dark:text-gray-200">مهامي المعلّقة</h3>
                            <p class="text-xs text-gray-400">{{ $pendingCount }} مهمة · {{ $overdueCount > 0 ? $overdueCount . ' متأخرة' : 'كلها في الوقت' }}</p>
                        </div>
                    </div>
                    <button
                        @click="showForm = !showForm"
                        class="flex items-center gap-1.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 px-3 py-2 rounded-lg transition-colors shadow-sm"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        إضافة مهمة
                    </button>
                </div>

                {{-- Add Task Form --}}
                <div
                    x-show="showForm"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    class="bg-blue-50/40 dark:bg-blue-950/20 border-b border-blue-100 dark:border-blue-900/40 px-4 py-3"
                >
                    <form wire:submit="saveNewTask" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">نوع المهمة</label>
                            <select wire:model="newType"
                                class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 focus:ring-2 focus:ring-primary-500 outline-none">
                                @foreach(\App\Models\LeadActivity::TYPES as $k => $v)
                                    @if(!in_array($k, ['status_change']))
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">الموضوع <span class="text-red-400">*</span></label>
                            <input wire:model="newSubject" type="text" placeholder="موضوع المهمة..."
                                class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 focus:ring-2 focus:ring-primary-500 outline-none">
                            @error('newSubject')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">العميل المحتمل <span class="text-red-400">*</span></label>
                            <select wire:model="newLeadId"
                                class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 focus:ring-2 focus:ring-primary-500 outline-none">
                                <option value="">-- اختر عميلاً --</option>
                                @foreach($leadsForSelect as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('newLeadId')<span class="text-xs text-red-500">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 font-medium">الوقت المجدول</label>
                            <input wire:model="newScheduledAt" type="datetime-local"
                                class="w-full text-sm rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 focus:ring-2 focus:ring-primary-500 outline-none">
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4 flex gap-2 justify-end pt-1">
                            <button type="button" @click="showForm = false"
                                class="text-sm text-gray-500 hover:text-gray-700 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 transition-colors">
                                إلغاء
                            </button>
                            <button type="submit"
                                class="text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 px-5 py-2 rounded-lg transition-colors shadow-sm">
                                حفظ المهمة
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Table --}}
                @if(count($todayTasks))
                <div class="overflow-x-auto">
                    <table class="w-full text-sm" dir="rtl">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700">
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-16">النوع</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">المهمة / العميل</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-32">الوقت</th>
                                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide w-24">الحالة</th>
                                <th class="px-4 py-2.5 w-24"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($todayTasks as $task)
                            @php
                            $typeStyles = [
                                'call'     => ['bg' => 'bg-green-100 dark:bg-green-900/40',  'text' => 'text-green-600 dark:text-green-400'],
                                'whatsapp' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/40', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                                'meeting'  => ['bg' => 'bg-blue-100 dark:bg-blue-900/40',   'text' => 'text-blue-600 dark:text-blue-400'],
                                'visit'    => ['bg' => 'bg-purple-100 dark:bg-purple-900/40','text' => 'text-purple-600 dark:text-purple-400'],
                                'email'    => ['bg' => 'bg-orange-100 dark:bg-orange-900/40','text' => 'text-orange-600 dark:text-orange-400'],
                                'sms'      => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/40','text' => 'text-yellow-600 dark:text-yellow-400'],
                                'note'     => ['bg' => 'bg-gray-100 dark:bg-gray-800',       'text' => 'text-gray-500 dark:text-gray-400'],
                            ];
                            $style = $typeStyles[$task['type']] ?? $typeStyles['note'];
                            @endphp
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors {{ $task['is_overdue'] ? 'bg-red-50/30 dark:bg-red-950/10' : '' }}">
                                {{-- Type icon --}}
                                <td class="px-4 py-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $style['bg'] }} {{ $style['text'] }}">
                                        @if($task['type'] === 'call')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                        @elseif($task['type'] === 'whatsapp')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        @elseif($task['type'] === 'meeting')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                        @elseif($task['type'] === 'visit')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                        @elseif($task['type'] === 'email')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                        @elseif($task['type'] === 'sms')
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                                        @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                        @endif
                                    </div>
                                </td>
                                {{-- Subject + Lead --}}
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-200 text-sm">{{ $task['subject'] ?? $task['type_label'] }}</p>
                                    @if($task['lead_name'])
                                    <a href="{{ $task['edit_url'] }}" class="text-xs text-primary-600 dark:text-primary-400 hover:underline mt-0.5 inline-block">
                                        {{ $task['lead_name'] }}
                                    </a>
                                    @endif
                                </td>
                                {{-- Time --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-medium {{ $task['is_overdue'] ? 'text-red-500 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                                        {{ $task['scheduled_at'] }}
                                    </span>
                                    @if($task['scheduled_date'] !== now()->format('Y-m-d'))
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $task['scheduled_date'] }}</p>
                                    @endif
                                </td>
                                {{-- Status --}}
                                <td class="px-4 py-3 text-center">
                                    @if($task['is_overdue'])
                                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 px-2 py-1 rounded-full">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse inline-block"></span>
                                        متأخر
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 px-2 py-1 rounded-full">
                                        اليوم
                                    </span>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td class="px-4 py-3 text-left">
                                    <button
                                        wire:click="completeTask({{ $task['id'] }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-green-600 hover:text-white bg-green-50 hover:bg-green-500 dark:bg-green-900/30 dark:hover:bg-green-600 border border-green-200 dark:border-green-800 rounded-lg px-3 py-1.5 transition-all"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        تم
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="flex flex-col items-center justify-center py-14 gap-3">
                    <div class="w-16 h-16 rounded-2xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-9 h-9 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="font-semibold text-gray-700 dark:text-gray-300">لا توجد مهام معلّقة</p>
                        <p class="text-sm text-gray-400 mt-1">يوم منتج! أضف مهمة جديدة للبدء.</p>
                    </div>
                </div>
                @endif
            </div>

        </div>

        {{-- Right column: calendar + upcoming meetings --}}
        <div class="space-y-4">

            {{-- Calendar --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/></svg>
                    </div>
                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300">{{ now()->translatedFormat('F Y') }}</h3>
                </div>
                <div class="p-3">
                    <div class="grid grid-cols-7 gap-0.5 text-xs text-center mb-1.5">
                        @foreach(['أح','إث','ثل','أر','خم','جم','سب'] as $d)
                        <div class="text-gray-400 font-medium py-1">{{ $d }}</div>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-0.5 text-xs text-center">
                        @foreach($calendarDays as $day)
                        @if($day === null)
                        <div></div>
                        @else
                        <div class="relative rounded-lg py-1.5 cursor-default transition-colors
                            {{ $day['is_today']
                                ? 'bg-primary-600 text-white font-bold shadow-sm'
                                : ($day['count'] > 0 ? 'hover:bg-primary-50 dark:hover:bg-primary-950/30' : 'hover:bg-gray-50 dark:hover:bg-gray-800') }}
                        ">
                            {{ $day['day'] }}
                            @if($day['count'] > 0)
                            <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full {{ $day['is_today'] ? 'bg-white/80' : 'bg-primary-400' }}"></span>
                            @endif
                        </div>
                        @endif
                        @endforeach
                    </div>
                    <p class="text-center text-xs text-gray-400 mt-3 flex items-center justify-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-400 inline-block"></span>
                        النقطة = لديك موعد في هذا اليوم
                    </p>
                </div>
            </div>

            {{-- Upcoming meetings --}}
            @if(count($upcomingMeetings))
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden shadow-sm">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300">مواعيد الأسبوع</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($upcomingMeetings as $meeting)
                    <div class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <span class="text-xs font-bold text-center w-12 shrink-0 bg-primary-50 dark:bg-primary-950/30 text-primary-600 dark:text-primary-400 px-1 py-1 rounded-lg leading-tight">
                            {{ $meeting['day_label'] }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $meeting['subject'] ?? $meeting['type_label'] }}</p>
                            @if($meeting['lead_name'])
                            <a href="{{ $meeting['edit_url'] }}" class="text-xs text-primary-600 hover:underline truncate block">{{ $meeting['lead_name'] }}</a>
                            @endif
                        </div>
                        <span class="text-xs font-mono text-gray-400 shrink-0">{{ substr($meeting['scheduled_at'], 11, 5) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>
    </div>

</div>

<script>
document.addEventListener('livewire:initialized', function () {
    if (!('Notification' in window)) return;

    function fireNotifications() {
        if (Notification.permission !== 'granted') return;

        const tasks = @json(array_values(array_filter($todayTasks, fn($t) => $t['is_overdue'])));
        tasks.forEach(function(task) {
            new Notification('⚠ مهمة متأخرة', {
                body: (task.subject || task.type_label) + (task.lead_name ? ' — ' + task.lead_name : ''),
                icon: '/favicon.ico',
                dir: 'rtl',
                tag: 'task-' + task.id,
            });
        });

        const reminders = @json($dueReminders);
        reminders.forEach(function(r) {
            new Notification('🔔 تذكير مستحق', {
                body: r.title + (r.lead_name ? ' — ' + r.lead_name : ''),
                icon: '/favicon.ico',
                dir: 'rtl',
                tag: 'reminder-' + r.id,
            });
        });
    }

    if (Notification.permission === 'default') {
        Notification.requestPermission().then(function(perm) {
            if (perm === 'granted') fireNotifications();
        });
    } else {
        fireNotifications();
    }
});
</script>
</x-filament-panels::page>
