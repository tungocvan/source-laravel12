<div class="bg-white border border-gray-200 rounded-lg shadow-sm">
    @if (session('success'))
        <div class="m-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="p-4 border-b border-gray-200 bg-gray-50/50">
        <h3 class="font-semibold text-gray-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Lịch sử Backup
        </h3>
        <div class="mt-2 flex flex-wrap gap-2 text-xs text-gray-500">
            @foreach ($backupDirectories as $directory)
                <code class="rounded bg-white px-2 py-1 ring-1 ring-gray-200">{{ $directory }}</code>
            @endforeach
        </div>
    </div>

    <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
        @forelse($backups as $file)
            <div class="p-4 hover:bg-gray-50 transition-colors group">
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-gray-900 break-all">{{ $file['name'] }}</p>
                        <div class="flex items-center gap-3 text-xs text-gray-500">
                            <span>{{ number_format($file['size'] / 1024, 2) }} KB</span>
                            <span>{{ \Carbon\Carbon::createFromTimestamp($file['time'])->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    {{-- Nút Download thông qua Controller chuẩn --}}
                    <a href="{{ route('admin.system.database.download', $file['name']) }}"
                        class="px-2 py-1 bg-blue-50 text-blue-600 rounded text-xs font-medium hover:bg-blue-100">
                        Download
                    </a>

                    @if ($file['size'] <= \Modules\System\Jobs\SendDatabaseBackupEmail::MAX_ATTACHMENT_BYTES)
                        <button wire:click="openEmailModal('{{ $file['name'] }}')"
                            class="rounded bg-violet-50 px-2 py-1 text-xs font-medium text-violet-700 hover:bg-violet-100">
                            Gửi email
                        </button>
                    @else
                        <span class="rounded bg-gray-100 px-2 py-1 text-[10px] text-gray-500">Không gửi email: trên 10MB</span>
                    @endif

                    @if ($file['is_full'])
                        <button wire:click="restoreBackup('{{ $file['name'] }}')"
                            wire:confirm="CẢNH BÁO: Hệ thống sẽ tự tạo một backup an toàn, sau đó ghi đè database hiện tại bằng '{{ $file['name'] }}'. Tiếp tục?"
                            wire:loading.attr="disabled"
                            class="px-2 py-1 bg-red-600 text-white rounded text-[10px] font-bold hover:bg-red-700 transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="restoreBackup('{{ $file['name'] }}')">RESTORE</span>
                            <span wire:loading wire:target="restoreBackup('{{ $file['name'] }}')">ĐANG RESTORE...</span>
                        </button>
                    @else
                        <span class="rounded bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700">Backup một bảng</span>
                    @endif

                    <button wire:click="deleteBackup('{{ $file['name'] }}')"
                        wire:confirm="Xóa vĩnh viễn file backup '{{ $file['name'] }}'? Thao tác này không thể hoàn tác."
                        class="px-2 py-1 rounded bg-gray-100 text-xs font-medium text-red-600 hover:bg-red-50">
                        Xóa
                    </button>
                </div>
            </div>
        @empty
            <div class="p-8 text-center">
                <p class="text-sm text-gray-400">Chưa có bản backup nào được lưu.</p>
            </div>
        @endforelse
    </div>

    @if ($showEmailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="text-lg font-bold text-gray-900">Gửi backup qua email</h2>
                <p class="mt-1 break-all text-sm text-gray-500">{{ $emailBackupFile }}</p>

                <div class="mt-5">
                    <label for="backup-recipient-email" class="mb-1 block text-sm font-medium text-gray-700">Email nhận file</label>
                    <input id="backup-recipient-email"
                           type="email"
                           wire:model="backupEmail"
                           wire:keydown.enter.prevent="sendBackupEmail"
                           placeholder="admin@example.com"
                           class="w-full rounded-lg border px-3 py-2 focus:border-indigo-500 focus:ring-indigo-500 @error('backupEmail') border-red-500 @else border-gray-300 @enderror">
                    @error('backupEmail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @error('emailBackupFile')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" wire:click="$set('showEmailModal', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Hủy</button>
                    <button type="button" wire:click="sendBackupEmail" wire:loading.attr="disabled" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="sendBackupEmail">Gửi file SQL</span>
                        <span wire:loading wire:target="sendBackupEmail">Đang đưa vào hàng đợi...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
